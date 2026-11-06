<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Queue\QueueFactory;
use Drupal\SwsDrush\Helpers\EnvironmentDetector;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Client\ClientInterface;

/**
 * Press helper service.
 */
final class PressHelper implements PressHelperInterface {

  /**
   * FileMaker find endpoint for book cover records.
   */
  const COVERS_ENDPOINT = 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Covers/_find';

  /**
   * FileMaker find endpoint for ebook retailer & format records.
   */
  const EBOOK_RETAILERS_ENDPOINT = 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/EbookRetailers/_find';

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
    // The queue worker only downloads covers on production. Queueing anywhere
    // else would just repeat the same API request on every cron run.
    if (!EnvironmentDetector::isProdEnv()) {
      return;
    }

    // Wait for the current batch to finish so items aren't queued twice.
    $queue = $this->queueFactory->get('press_cover_downloader');
    if ($queue->numberOfItems() > 0 || !$this->getApiToken()) {
      return;
    }

    // Find the covers that are flagged to be updated. The API is slow to
    // respond so limiting to only 100 items will make it more reliable.
    $covers = $this->findRecords(self::COVERS_ENDPOINT, [
      'query' => [['flag' => '=x']],
      'limit' => 100,
    ]);

    // Create a cron queue task for each cover.
    foreach ($covers as $cover) {
      if (!empty($cover['recordId'])) {
        $queue->createItem($cover['recordId']);
      }
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
   * {@inheritdoc}
   */
  public function getEbookData(): array {
    // Multiple query objects in a FileMaker find are OR'd, so a single search
    // returns both the retailer links and the format flags.
    $records = $this->searchFileMaker(self::EBOOK_RETAILERS_ENDPOINT, [
      'query' => [
        ['link' => 'http'],
        ['format_pdf' => '=yes'],
        ['format_epub' => '=yes'],
      ],
      'sort' => [['fieldName' => 'retailer', 'sortOrder' => 'ascend']],
    ]);

    $data = [];
    foreach ($records as $record) {
      $fields = $record['fieldData'] ?? [];
      $work_id = (int) ($fields['work_id_number'] ?? 0);
      if (!$work_id) {
        continue;
      }

      $uri = trim((string) ($fields['link'] ?? ''));
      if ($uri && UrlHelper::isValid($uri, TRUE)) {
        $data[$work_id]['retailers'][] = [
          'title' => $fields['retailer'] ?? '',
          'uri' => $uri,
        ];
      }

      $epub = ($fields['format_epub'] ?? '') == 'yes';
      $pdf = ($fields['format_pdf'] ?? '') == 'yes';
      // Only works with at least one format record get the flags, a work is
      // flagged if any of its records has the format.
      if ($epub || $pdf) {
        $data[$work_id]['epub'] = !empty($data[$work_id]['epub']) || $epub;
        $data[$work_id]['pdf'] = !empty($data[$work_id]['pdf']) || $pdf;
      }
    }
    return $data;
  }

  /**
   * Perform a find on the FileMaker API and return all matching records.
   *
   * @param string $endpoint
   *   API endpoint, should end in `_find`.
   * @param array $body
   *   Keyed array of search parameters.
   * @param int $limit
   *   Page limit per request.
   *
   * @return array
   *   All records returned by the search.
   *
   * @throws \Exception
   *   When authentication or a request fails.
   *
   * @see https://help.claris.com/en/data-api-guide/content/perform-find-request.html
   */
  protected function searchFileMaker(string $endpoint, array $body, int $limit = 10000): array {
    $records = [];
    $body['limit'] = $limit;
    $page = 0;
    do {
      $body['offset'] = ($page * $limit) + 1;
      $page_records = $this->findRecords($endpoint, $body);
      $records = [...$records, ...$page_records];
      $page++;
    }
    while (count($page_records) == $limit);

    return $records;
  }

  /**
   * Perform a single find request on the FileMaker API.
   *
   * @param string $endpoint
   *   API endpoint, should end in `_find`.
   * @param array $body
   *   Keyed array of search parameters, including any limit and offset.
   *
   * @return array
   *   Records in the response, empty when nothing matches the find.
   *
   * @throws \Exception
   *   When authentication or the request fails.
   */
  protected function findRecords(string $endpoint, array $body): array {
    $token = $this->getApiToken();
    if (!$token) {
      throw new \RuntimeException('Unable to authenticate with the FileMaker API.');
    }

    try {
      $response = (string) $this->httpClient->request('POST', $endpoint, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
        'body' => json_encode($body),
        'timeout' => 60,
      ])->getBody();
    }
    catch (\Exception $e) {
      if (self::isNoRecordsError($e)) {
        return [];
      }
      throw $e;
    }

    $response = json_decode($response, TRUE, 512, JSON_THROW_ON_ERROR);
    return $response['response']['data'] ?? [];
  }

  /**
   * Check if the exception is FileMaker reporting that no records matched.
   *
   * FileMaker responds to a find with no results as a 500 error with the
   * message code 401, instead of an empty result.
   *
   * @param \Exception $e
   *   Exception thrown by the http client.
   *
   * @return bool
   *   True if the find had no matching records.
   */
  protected static function isNoRecordsError(\Exception $e): bool {
    if ($e instanceof RequestException && $e->getResponse()) {
      $body = json_decode((string) $e->getResponse()->getBody(), TRUE);
      if (is_array($body) && isset($body['messages'])) {
        return in_array('401', array_column($body['messages'], 'code'), TRUE);
      }
    }
    // Fall back to the message, which includes the start of the response body.
    return str_contains($e->getMessage(), 'No records match the request');
  }

}
