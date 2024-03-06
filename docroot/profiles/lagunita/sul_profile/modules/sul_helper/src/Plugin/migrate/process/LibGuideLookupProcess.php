<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use GuzzleHttp\ClientInterface;

/**
 * Provides a libguide_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: libguide_lookup
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "libguide_lookup"
 * )
 */
class LibGuideLookupProcess extends LibLookupProcessBase {

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    foreach ($this->sulService->getLibGuides() as $guide) {
      if (is_array($value)) {
        if (in_array(strtolower($guide['owner']['email']), $value)) {
          return $guide['id'];
        }
      }
      elseif ($value == strtolower($guide['owner']['email'])) {
        return $guide['id'];
      }
    }
    return NULL;
  }

}
