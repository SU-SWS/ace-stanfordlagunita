<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

/**
 * Press helper.
 */
interface PressHelperInterface {

  /**
   * Get the filemaker api token.
   *
   * @return string|false
   *   Token or false and any failure.
   */
  public function getApiToken(): string|false;

  /**
   * Query the FileMaker API for images that have been updated, queue them.
   */
  public function queueCoverImages(): void;

  /**
   * Using the first url with no parameters, build a list of all urls to fetch.
   *
   * @return string[]
   *   Urls with page and offset parameters.
   */
  public function getMigrationUrls(string $firstUrl): array;

}
