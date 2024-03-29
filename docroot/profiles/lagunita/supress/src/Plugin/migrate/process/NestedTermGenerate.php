<?php

namespace Drupal\supress\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\core\Plugin\ContainerFactoryPluginInterface;

/**
 * This plugin handles nested taxonomy terms.
 *
 * @MigrateProcessPlugin(
 *   id = "nested_term_generate"
 * )
 */
class NestedTermGenerate extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $vid = $this->configuration['vocabulary'];
    $delimiter = $this->configuration['delimiter'];
    $parent = 0;

    $terms = explode($delimiter, $value);
    foreach ($terms as $term_name) {
      $term_name = trim($term_name);
      if ($term_name) {
        // See if it already exists.
        $term = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'name' => $term_name,
            'vid' => $vid,
          ]);

        // If it doesn't, create it.
        if (empty($term)) {
          $term = Term::create([
            'name' => $term_name,
            'vid' => $vid,
            'parent' => [$parent],
          ]);
          $term->save();
        }
        else {
          $term = reset($term);
        }

        $parent = $term->id();
      }

    }

    return $parent;
  }

}
