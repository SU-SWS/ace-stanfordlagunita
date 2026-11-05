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
   * Fetch ebook retailer links and format flags from FileMaker.
   *
   * @return array
   *   Keyed by work id number. Each value may contain:
   *   - retailers: list of ['title' => string, 'uri' => string].
   *   - epub: bool, only set for works that have an ebook format record.
   *   - pdf: bool, only set for works that have an ebook format record.
   *
   * @throws \Exception
   *   When the API can not be reached or authentication fails.
   */
  public function getEbookData(): array;

}
