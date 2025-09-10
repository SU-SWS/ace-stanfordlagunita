<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Http\Client\ClientInterface;

/**
 * Filemaker service to act on the API.
 */
final class PressFilemaker implements PressFilemakerInterface {

  const API_TOKEN_CACHE_KEY = 'supress_helper.filemaker_api_token';
  const EBOOK_FORMATS_CACHE_KEY = 'supress_helper.filemaker_ebook_formats';
  const EBOOK_RETAILERS_CACHE_KEY = 'supress_helper.filemaker_ebook_retailers';

  /**
   * Constructs a PressFilemaker object.
   */
  public function __construct(
    private readonly CacheBackendInterface $cacheDefault,
    private readonly ClientInterface $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly QueueFactory $queueFactory,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function getApiToken(): ?string {
    if ($cached_item = $this->cacheDefault->get(self::API_TOKEN_CACHE_KEY)) {
      return $cached_item->data;
    }
    $config_page_loader = \Drupal::service('config_pages.loader');
    $client_id = $config_page_loader->getValue('stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value');
    $client_secret = $config_page_loader->getValue('stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value');

    if (
      !$client_id ||
      !$client_secret ||
      InstallerKernel::installationAttempted()
    ) {
      return NULL;
    }

    try {
      // Grab the OAuth-like token from the api.
      $token_response = $this->httpClient->request('POST', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/sessions', [
        'headers' => ['Content-Type' => 'application/json'],
        'auth' => [$client_id, $client_secret],
      ]);
      $token = json_decode((string) $token_response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $token = $token['response']['token'];
      $this->cacheDefault->set(self::API_TOKEN_CACHE_KEY, $token, time() + 300);
      return $token;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('supress_helper')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function search(string $endpoint, array $body, int $limit = 1000): array {
    $token = $this->getApiToken();
    if (!$token) {
      return [];
    }

    $fetch_more = TRUE;
    $page = 0;
    $return_data = [];

    $body['limit'] = $limit;

    while ($fetch_more) {
      $body['offset'] = ($page * $limit) + 1;
      $result_page = (string) $this->httpClient->request('POST', $endpoint, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
        'body' => json_encode($body),
        'timeout' => 60,
      ])->getBody();
      $result_page = json_decode($result_page, TRUE);

      $return_data = [
        ...$return_data,
        ...$result_page['response']['data'],
      ];

      $fetch_more = $result_page['response']['dataInfo']['returnedCount'] == $limit;
      $page++;
    }

    return $return_data;
  }

  /**
   * {@inheritDoc}
   */
  public function getEbookFormats(): array {
    if ($cached_item = $this->cacheDefault->get(self::EBOOK_FORMATS_CACHE_KEY)) {
      return $cached_item->data;
    }
    $search_body = [
      'query' => [
        ['format_pdf' => '=yes'],
        ['format_epub' => '=yes'],
      ],
    ];
    try {
      $formats = $this->search('https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/EbookRetailers/_find', $search_body, 10000);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('supress')
        ->error('An error occurred when fetching ebook formats: @error', ['@error' => $e->getMessage()]);
      $formats = [];
    }

    $book_formats = [];
    foreach ($formats as $item) {
      if (empty($book_formats[(int) $item['fieldData']['work_id_number']]['epub'])) {
        $book_formats[(int) $item['fieldData']['work_id_number']]['epub'] = $item['fieldData']['format_epub'] == 'yes';
      }

      if (empty($book_formats[(int) $item['fieldData']['work_id_number']]['pdf'])) {
        $book_formats[(int) $item['fieldData']['work_id_number']]['pdf'] = $item['fieldData']['format_pdf'] == 'yes';
      }
    }
    ksort($book_formats);

    $this->cacheDefault->set(self::EBOOK_FORMATS_CACHE_KEY, $book_formats, time() + 60 * 60 * 24);
    return $book_formats;
  }

  /**
   * {@inheritDoc}
   */
  public function getEbookRetailers(): array {
    if ($cached_item = $this->cacheDefault->get(self::EBOOK_RETAILERS_CACHE_KEY)) {
      return $cached_item->data;
    }
    $search_body = [
      'query' => [['link' => 'http']],
      'sort' => [['fieldName' => 'retailer', 'sortOrder' => 'ascend']],
    ];
    try {
      $retailers = $this->search('https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/EbookRetailers/_find', $search_body, 10000);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('supress')
        ->error('An error occurred when fetching ebook retailer links: @error', ['@error' => $e->getMessage()]);
      $retailers = [];
    }

    foreach ($retailers as &$record) {
      $record = [
        'work_id_number' => $record['fieldData']['work_id_number'],
        'title' => $record['fieldData']['retailer'],
        'uri' => trim($record['fieldData']['link']),
      ];
    }
    $retailers = array_filter($retailers, fn($item) => UrlHelper::isValid($item['uri']));

    $this->cacheDefault->set(self::EBOOK_RETAILERS_CACHE_KEY, $retailers, time() + 60 * 60 * 24);
    return $retailers;
  }

  /**
   * {@inheritDoc}
   */
  public function getSocialLinks(): array {
    $page_num = 0;
    $fetch_more = TRUE;
    $pages = [];

    while ($fetch_more) {
      $offset = $page_num * 1000 + 1;
      $page = json_decode((string) $this->httpClient->request('GET', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/SocialMedia/records', [
        'query' => [
          '_limit' => 1000,
          '_offset' => $offset,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getApiToken(),
        ],
        'timeout' => 15,
      ])->getBody(), TRUE);
      $pages = [...$pages, ...$page['response']['data']];
      $fetch_more = $page['response']['dataInfo']['returnedCount'] == 1000;
      $page_num++;
    }

    $data = [];
    foreach ($pages as $item) {
      $item = $item['fieldData'];
      // Make sure there's a title.
      if (empty($item['title'])) {
        continue;
      }

      $work_id = $item['work_id_number'];
      $item_html = "<h2><a href=\"{$item['url']}\">{$item['title']}</a></h2><p>";
      if (!empty($item['description'])) {
        preg_match_all('/(<iframe.*?>)/', $item['description'], $iframes);
        if (!empty($iframes[1])) {
          foreach ($iframes[1] as $iframe) {
            $modified_iframe = $iframe;
            if (!str_contains(' title="', $iframe)) {
              $iframe_title = Html::escape($item['title']);
              $modified_iframe = str_replace('<iframe ', "<iframe title=\"$iframe_title\" ", $modified_iframe);
            }
            $item['description'] = str_replace($iframe, $modified_iframe, $item['description']);
          }
        }
        $item_html .= $item['description'];
      }
      if (!empty($item['source'])) {
        $item_html .= "<br />&mdash;{$item['source']}";
      }
      $item_html .= "</p>";
      $item_html = str_replace('<p></p>', '', $item_html);

      if (isset($data[$work_id])) {
        $data[$work_id]['html'] .= $item_html;
      }
      else {
        $data[$work_id] = [
          'workId' => $work_id,
          'html' => $item_html,
        ];
      }
    }
    return array_values($data);
  }

  /**
   * {@inheritDoc}
   */
  public function queueCoverImages(): void {
    $queue = $this->queueFactory->get('press_cover_downloader');
    if ($queue->numberOfItems() > 0) {
      return;
    }

    // Post a query to find the covers that are flagged to be updated. The API
    // is slow to respond so limiting to only 100 items will make it more
    // reliable.
    try {
      $covers_response = (string) $this->httpClient->request('POST', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Covers/_find', [
        'query' => ['_limit' => 100],
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getApiToken(),
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

}
