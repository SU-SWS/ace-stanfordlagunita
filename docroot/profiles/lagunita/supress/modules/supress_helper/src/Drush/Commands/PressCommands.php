<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Drush\Commands;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\supress_helper\PressHelperInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A Drush commandfile.
 */
final class PressCommands extends DrushCommands {

  use AutowireTrait;

  protected $pressLogger;

  /**
   * Constructs a PressCommands object.
   */
  public function __construct(
    private readonly Token $token,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ClientInterface $httpClient,
    #[Autowire(service: 'cache.default')]
    private readonly CacheBackendInterface $cacheDefault,
    LoggerChannelFactoryInterface $loggerFactory,
    #[Autowire(service: 'supress_helper')]
    private readonly PressHelperInterface $pressHelper,
  ) {
    parent::__construct();
    $this->pressLogger = $loggerFactory->get('supress');
  }

  /**
   * Build books.
   */
  #[CLI\Command(name: 'supress:unpublish-orphans')]
  public function supUnpublishOprhans() {
    $work_ids = [];
    foreach ($this->getAllBooks() as $item) {
      $work_ids[] = $item['fieldData']['work_id_number'];
    }

    if (!$work_ids) {
      throw new CommandFailedException('Empty work ids');
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $ids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'sup_book')
      ->condition('status', 1)
      ->condition('sup_book_work_id_number', $work_ids, 'NOT IN')
      ->execute();
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($node_storage->loadMultiple($ids) as $node) {
      $this->pressLogger->notice('Unpublished orphan book: %title', ['%title' => $node->label()]);
      $node->setUnpublished()->save();
    }
  }

  /**
   * Get all book data.
   *
   * @return array
   *   Array of book data.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   *   When unable to authenticate with the API.
   * @throws \JsonException
   */
  protected function getAllBooks() {
    $cache = $this->cacheDefault->get('press-all-books');
    if ($cache) {
      return $cache->data;
    }
    $token = $this->pressHelper->getApiToken();
    if (!$token) {
      throw new CommandFailedException('Unable to authenticate with the FileMaker API. Check the FileMaker credentials in the site settings.');
    }
    $page = 0;
    $limit = 5000;
    $fetch_more = TRUE;

    $data = [];
    while ($fetch_more) {
      $this->say('Fetching page ' . $page + 1);
      $offset = $page * $limit + 1;
      $page_response = $this->httpClient->get("https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Web/records?_limit=$limit&_offset=$offset", ['headers' => ['Authorization' => "Bearer $token"]]);
      $page_details = json_decode((string) $page_response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $fetch_more = $page_details['response']['dataInfo']['returnedCount'] == $limit;
      $page++;

      foreach ($page_details['response']['data'] as $item) {
        $data[] = $item;
      }
    }
    $this->cacheDefault->set('press-all-books', $data, time() + (60 * 60 * 24));
    return $data;
  }

}
