<?php

namespace Drupal\supress_helper\Plugin\migrate_plus\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin converts a string to sentence case.
 *
 * @MigrateProcessPlugin(
 *   id = "sentence_case"
 * )
 */
class SentenceCase extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Convert the string to lowercase.
    $value = strtolower($value);

    // Convert the first character to uppercase.
    $value = ucfirst($value);

    return $value;
  }

}
