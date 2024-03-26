<?php

namespace Drupal\supress\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * This plugin handles nested taxonomy terms.
 *
 * @MigrateProcessPlugin(
 *   id = "nested_term_generate"
 * )
 */
class NestedTermGenerate extends ProcessPluginBase {

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
      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $term_name,
          'vid' => $vid,
        ]);

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

    return $parent;
  }

}
