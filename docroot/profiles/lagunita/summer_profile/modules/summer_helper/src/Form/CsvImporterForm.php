<?php

namespace Drupal\summer_helper\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CsvImporterForm extends FormBase {

  private $entityTypeManager;

  private $database;

  private $cache;

  private $fileSystem;
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('cache.default'),
      $container->get('database')
    );
  }

  /**
   * CsvImporterForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager Service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Caching service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, FileSystemInterface $fileSystem, CacheBackendInterface $cache, Connection $database) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->cache = $cache;
    $this->database = $database;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = $this->getHelpText();
    $form['csv'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#upload_location' => 'temporary://',
      '#upload_validators' => ['file_validate_extensions' => ['csv']],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#name' => 'import',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->getTriggeringElement()['#name'] !== 'import') {
      return;
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')
      ->load($form_state->getValue(['csv', 0]));
    if (!$file || !file_exists($file->getFileUri())) {
      $form_state->setError($form['csv'], $this->t('Unable to load file'));
      return;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $before_count = $this->getNodeCount();
    // Invalidate the migrations so that we can alter the plugin after setting
    // the state for the file path.
    Cache::invalidateTags(['migration_plugins']);
    $db_table = 'migrate_map_' . $form_state->getValue('migration');
    if ($this->database->schema()->tableExists($db_table)) {
      $this->database->truncate($db_table)->execute();
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')
      ->load($form_state->getValue(['csv', 0]));
    $file_path = $this->fileSystem->realpath($file->getFileUri());
    $migration_id = $form_state->getValue('migration');

    // Set the cache for the csv file path for only 4 minutes since it will be
    // fast for the importer.
    $this->cache->set('migration:csv_path', [
      'migration' => $migration_id,
      'path' => $file_path,
    ], time() + 240);

    try {
      $migration = $this->getMigration($migration_id);
      \Drupal::service('stanford_migrate')->executeMigration();
      $file->delete();

      $count = $this->getNodeCount() - $before_count;
      $this->messenger()
        ->addStatus($this->t('Imported %count items.', ['%count' => $count]));
      // @codeCoverageIgnoreStart
    }
    catch (\Exception $e) {
      $this->logger('cardinal_service')
        ->error($this->t('CSV Importer failed: @message', ['@message' => $e->getMessage()]));
      $this->messenger()
        ->addError($this->t('Unable to import CSV. Review the logs for more information'));
    }
    // @codeCoverageIgnoreEnd

    $db_table = 'migrate_map_' . $form_state->getValue('migration');
    if ($this->database->schema()->tableExists($db_table)) {
      $this->database->truncate($db_table)->execute();
    }
  }

  protected function getHelpText() {
    $help[] = [
      '#markup' => $this->t('Download an empty CSV template for Courses.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    return $help;
  }

  protected function getMigration($migration_id) {
    try {
      $migrations = \Drupal::service('stanford_migrate')->getMigrationList();

      foreach ($migrations as $group) {
        if (isset($group[$migration_id])) {
          return $group[$migration_id];
        }
      }
      // @codeCoverageIgnoreStart
    }
    catch (\Exception $e) {
      return FALSE;
    }
    //@codeCoverageIgnoreEnd
  }

  protected function getNodeCount() {
    return (int) $this->database->select('node', 'n')
      ->fields('n')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  public function getFormId() {
    return 'csv_importer_form';
  }
}
