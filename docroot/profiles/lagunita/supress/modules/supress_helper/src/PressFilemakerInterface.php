<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

/**
 * @todo Add interface description.
 */
interface PressFilemakerInterface {

  /**
   * Get the filemaker api token.
   *
   * @return string|null
   *   Token string or null if any errors.
   */
  public function getApiToken(): ?string;

  /**
   * Perform a search on the filemaker API and return all records.
   *
   * @param string $endpoint
   *   API endpoint, should end in `_find`.
   * @param array $body
   *   Keyed array of search parameters.
   * @param int $limit
   *   Page limit per request.
   *
   * @return array
   *   Array of all records returned in the search.
   *
   * @see https://help.claris.com/en/data-api-guide/content/perform-find-request.html
   */
  public function search(string $endpoint, array $body, int $limit = 1000): array;

  /**
   * Get a list of all books with epub or pdf formats.
   *
   * @return array
   *   Keyed array of work id and its respective formats.
   */
  public function getEbookFormats(): array;

  /**
   * Get the list of all ebooks from the FileMaker API.
   *
   * @return array
   *   Array of retailer data.
   */
  public function getEbookRetailers(): array;

  /**
   * Get the list of all social links form the FileMaker API.
   *
   * @return array
   *   Social links data.
   */
  public function getSocialLinks(): array;

  /**
   * Fetch cover image data from the FileMaker API and queue them to download.
   */
  public function queueCoverImages(): void;

}
