<?php

namespace Drupal\sul_helper\Plugin\migrate_plus\data_parser;

use Drupal\stanford_migrate\Plugin\migrate_plus\data_parser\LocalistJson;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "sul_localist_json",
 *   title = @Translation("SUL Localist JSON")
 * )
 */
class SulLocalistJson extends LocalistJson {

  protected function getSourceData(string $url): array {
    $source_data = parent::getSourceData($url);

    $event_ids = [];
    foreach ($source_data as $key => $item) {
      if (in_array($item['event']['id'], $event_ids)) {
        unset($source_data[$key]);
        continue;
      }
      $event_ids[] = $item['event']['id'];
    }
    return array_values($source_data);
  }

}