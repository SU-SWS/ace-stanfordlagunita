<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Hook;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\supress_helper\PressHelperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * SuPress hooks.
 */
class PressHooks {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly ModuleExtensionList $moduleExtensionList,
    #[Autowire(service: 'supress_helper')]
    protected readonly PressHelperInterface $pressHelper,
    protected readonly MigrationPluginManagerInterface $migrationManager,
  ) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#attached']['library'][] = 'supress_helper/admin';
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'ckeditor5') {
      $module_path = $this->moduleExtensionList->getPath('supress_helper');
      $libraries['internal.drupal.ckeditor5.stylesheets'] = [
        'css' => [
          'theme' => [
            "/$module_path/styles/dist/css/ckeditor.css" => [],
          ],
        ],
      ];
    }
  }

  /**
   * Implements hook_paragraphs_behavior_info_alter().
   */
  #[Hook('paragraphs_behavior_info_alter')]
  public function paragraphsBehaviorInfoAlter(array &$paragraphs_behavior): void {
    $paragraphs_behavior['su_card_styles']['class'] = '\Drupal\supress_helper\Plugin\paragraphs\Behavior\PressCardBehaviors';
  }

  /**
   * Implements hook_ENTITY_TYPE_access() for media entities.
   */
  #[Hook('media_access')]
  public function mediaAccess(MediaInterface $media, string $operation, AccountInterface $account): AccessResultInterface {
    $route = $this->routeMatch->getRouteName();
    return AccessResult::forbiddenIf($route != 'graphql.query.graphql_compose_server' && $media->bundle() == 'sup_protected_file' && $account->isAnonymous());
  }

  /**
   * Implements hook_entity_create_access().
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, array $context, ?string $entity_bundle): AccessResultInterface {
    // Disable the banner paragraph type.
    return AccessResult::forbiddenIf($entity_bundle == 'stanford_banner');
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->pressHelper->queueCoverImages();
  }

  /**
   * Implements hook_search_api_algolia_objects_alter().
   */
  #[Hook('search_api_algolia_objects_alter')]
  public function searchApiAlgoliaObjectsAlter(array &$objects, IndexInterface $index, array $items): void {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($objects as &$object) {
      // Useful to limit record data for local algolia testing.
      if (Settings::get('algolia_limit_record')) {
        $object['html'] = implode(' ', array_slice(explode(' ', $object['html'] ?? ''), 0, 10));
      }

      if (isset($object['book_subject'])) {
        $object['book_subject'] = is_array($object['book_subject']) ? $object['book_subject'] : [$object['book_subject']];
        $all_subjects = [];

        foreach ($object['book_subject'] as &$term_id) {
          $term = $term_storage->load($term_id);
          $all_subjects[] = $term->label();
          while ($parent = $term_storage->loadParents($term_id)) {
            $term = reset($parent);
            $all_subjects[] = $term->label();
            $term_id = $term->id();
          }
          $term_id = $term->label();
        }

        asort($all_subjects);
        asort($object['book_subject']);
        $object['book_subject_all'] = $all_subjects;
        $object['book_subject'] = array_values(array_unique($object['book_subject']));
      }
    }
  }

  #[Hook('entity_create')]
  public function pressCreate(EntityInterface $entity) {
    if (InstallerKernel::installationAttempted()) {
      return;
    }

    $migration = $this->getBookMigration();
    if (!$migration) {
      return;
    }

    // If an award is created (only from the importer), set the node that
    // relates to the award to be updated on the next import.
    if ($entity->getEntityTypeId() == 'press') {
      $migration->getIdMap()->setUpdate([
        'work_id_number' => $entity->get('work_id')->getString(),
      ]);
    }

    // If an image is created , set the node that relates to the award to be
    // updated on the next import.
    if (
      $entity instanceof MediaInterface &&
      $entity->bundle() == 'image' &&
      $entity->get('sup_book_work_id')->count()
    ) {
      $migration->getIdMap()->setUpdate([
        'work_id_number' => $entity->get('sup_book_work_id')->getString(),
      ]);
    }
  }

  /**
   * Get the book importer migration.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface|null
   *   The migration, or NULL if it doesn't exist.
   */
  protected function getBookMigration(): ?MigrationInterface {
    try {
      return $this->migrationManager->createInstance('sup_import_books') ?: NULL;
    }
    catch (PluginException) {
      return NULL;
    }
  }

  /**
   * Implements hook_viewfield_argument_suggestion_vocabs_alter().)
   */
  #[Hook('viewfield_argument_suggestion_vocabs_alter')]
  public function viewfieldArgVocabs(array &$vocabs, array $view) {
    if ($view['view'] == 'sup_books') {
      $vocabs[] = 'sup_book_subjects';
      $vocabs[] = 'sup_series';
      $vocabs[] = 'sup_book_tags';
      $vocabs[] = 'sup_imprints';
    }
  }

  /**
   * Clear the algolia index item immediately.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to delete.
   */
  protected static function clearAlgolia(NodeInterface $node) {
    if (\Drupal::hasService('search_api_algolia.helper')) {
      \Drupal::service('search_api_algolia.helper')->entityDelete($node);
    }
  }

}
