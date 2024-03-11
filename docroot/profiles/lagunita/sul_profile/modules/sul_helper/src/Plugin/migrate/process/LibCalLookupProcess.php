<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use GuzzleHttp\ClientInterface;

/**
 * Provides a libcal_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: libcal_lookup
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "libcal_lookup"
 * )
 */
class LibCalLookupProcess extends LibLookupProcessBase {

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    foreach ($this->sulService->getLibCalUsers() as $user) {
      if (is_array($value)) {
        if (in_array(strtolower($user['email']), $value)) {
          return $user['id'];
        }
      }
      elseif ($value == strtolower($user['email'])) {
        return $user['id'];
      }
    }
    return NULL;
  }

}
