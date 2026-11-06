<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate\source;

use Drupal\migrate\MigrateException;

/**
 * Builds paged urls for a FileMaker Data API layout's records.
 *
 * Classes using this trait must provide:
 * - $httpClient: \GuzzleHttp\ClientInterface.
 * - $pressHelper: \Drupal\supress_helper\PressHelperInterface.
 */
trait FileMakerPagedUrlsTrait {

  /**
   * Number of records to request per page.
   *
   * @var int
   */
  protected int $pageSize = 5000;

  /**
   * Build a url for each page of records in a FileMaker layout.
   *
   * @param string $url
   *   Layout records url, without any query parameters.
   *
   * @return string[]
   *   Urls with limit and offset parameters.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When unable to authenticate with the API.
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   When the record count request fails.
   */
  protected function getPagedUrls(string $url): array {
    $token = $this->pressHelper->getApiToken();
    if (!$token) {
      throw new MigrateException('Unable to authenticate with the FileMaker API.');
    }

    $urls = [];
    $total_records = $this->getRecordCount($url, $token);
    for ($offset = 1; $offset <= $total_records; $offset += $this->pageSize) {
      $urls[] = $url . '?' . http_build_query([
        '_limit' => $this->pageSize,
        '_offset' => $offset,
      ]);
    }
    return $urls;
  }

  /**
   * Get the total number of records in the layout.
   *
   * @param string $url
   *   Layout records url.
   * @param string $token
   *   API token.
   *
   * @return int
   *   Total records.
   */
  protected function getRecordCount(string $url, string $token): int {
    $response = $this->httpClient->request('GET', $url, [
      'query' => ['_limit' => 1],
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
      ],
      'timeout' => 15,
    ]);
    $response = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    return (int) ($response['response']['dataInfo']['totalRecordCount'] ?? 0);
  }

}
