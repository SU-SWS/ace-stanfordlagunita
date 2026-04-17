<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Queue\QueueFactory;
use Psr\Http\Client\ClientInterface;

/**
 * Press helper service.
 */
final class PressHelper implements PressHelperInterface {

  /**
   * Constructs a PressHelper object.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigPagesLoaderServiceInterface $configPageLoader,
    protected QueueFactory $queueFactory,
    protected CacheBackendInterface $cache,
  ) {}

  /**
   * Queue up cover images for future cron jobs.
   */
  public function queueCoverImages(): void {
    $token = $this->getApiToken();
    if (!$token) {
      return;
    }

    $queue = $this->queueFactory->get('press_cover_downloader');
    if ($queue->numberOfItems() > 0) {
      return;
    }

    // Post a query to find the covers that are flagged to be updated. The API
    // is slow to respond so limiting to only 100 items will make it more
    // reliable.
    try {
      $covers_response = (string) $this->httpClient->request('POST', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Covers/_find?_limit=100', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
        'body' => json_encode(['query' => [['flag' => '=x']]]),
        'timeout' => 60,
      ])->getBody();
    }
    catch (\Exception $e) {
      // When there are no covers that match, the response is a 500 "error" even
      // though it should actually be a 204. Only log when the message doesn't
      // mention "no records".
      if (!str_contains($e->getMessage(), 'No records match the request')) {
        throw $e;
      }
      return;
    }

    // Create a cron queue task for each cover.
    $covers = json_decode($covers_response, TRUE, 512, JSON_THROW_ON_ERROR);
    foreach ($covers['response']['data'] as $cover) {
      $queue->createItem($cover['recordId']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiToken(): string|false {
    if ($cached_item = $this->cache->get('filemaker_api_token')) {
      return $cached_item->data;
    }

    $client_id = $this->configPageLoader->getValue('stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value');
    $client_secret = $this->configPageLoader->getValue('stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value');

    if (
      !$client_id ||
      !$client_secret ||
      InstallerKernel::installationAttempted()
    ) {
      return FALSE;
    }

    try {
      // Grab the OAuth-like token from the api.
      $token_response = $this->httpClient->request('POST', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/sessions', [
        'headers' => ['Content-Type' => 'application/json'],
        'auth' => [$client_id, $client_secret],
      ]);
      $token = json_decode((string) $token_response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $token = $token['response']['token'];
      $this->cache->set('filemaker_api_token', $token, time() + 60 * 5);
      return $token;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * @inheritDoc
   */
  public function getMigrationUrls(string $firstUrl): array {
    $urls = [];
    $totalRecords = $this->getRecordCount($firstUrl);
    for ($i = 0; $i < $totalRecords; $i += 1000) {
      $query = http_build_query([
        '_limit' => 1000,
        '_offset' => $i + 1,
      ]);
      $urls[] = "$firstUrl?$query";
    }
    return $urls;
  }

  /**
   * Get the total number of records from the api.
   *
   * @param string $recordsUrl
   *   Base API path.
   *
   * @return int
   */
  protected function getRecordCount(string $recordsUrl): int {
    $cacheKey = 'press-record-count:' . substr(md5($recordsUrl), 0, 5);
    if ($data = $this->cache->get($cacheKey)) {
      return $data->data;
    }
    $token = $this->getApiToken();

    if (!$token) {
      return 0;
    }
    try {
      $response = json_decode((string) $this->httpClient->request('GET', $recordsUrl, [
        'query' => ['_limit' => 1],
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
        'timeout' => 15,
      ])->getBody(), TRUE);
    }
    catch (\Throwable $e) {
      return 0;
    }

    $this->cache->set($cacheKey, $response['response']['dataInfo']['totalRecordCount'], time() + 60 * 60 * 24 * 7, ['press-record-count']);
    return $response['response']['dataInfo']['totalRecordCount'] ?: 0;
  }

}
