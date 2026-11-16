<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\supress_helper\PressHelper;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for the PressHelper service.
 */
#[Group('supress_helper')]
#[CoversClass(PressHelper::class)]
class PressHelperTest extends UnitTestCase {

  /**
   * Cache key used by PressHelper for the API token.
   */
  const TOKEN_CACHE_KEY = 'filemaker_api_token';

  /**
   * Builds a PressHelper with the given dependencies.
   */
  protected function buildHelper(
    ClientInterface $client,
    ConfigPagesLoaderServiceInterface $configLoader,
    QueueFactory $queueFactory,
    CacheBackendInterface $cache,
  ): PressHelper {
    return new PressHelper($client, $configLoader, $queueFactory, $cache);
  }

  /**
   * Builds a PSR-7 response wrapping the given JSON-encodable value.
   */
  protected function buildResponse(mixed $data): ResponseInterface {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, is_string($data) ? $data : json_encode($data));
    rewind($resource);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(new Stream($resource));
    return $response;
  }

  /**
   * Returns a cache item object as CacheBackendInterface returns it.
   */
  protected function cacheItem(mixed $data): \stdClass {
    $item = new \stdClass();
    $item->data = $data;
    return $item;
  }

  /**
   * Builds a default config-page loader that returns the given credentials.
   */
  protected function buildConfigLoader(string $user = 'api-user', string $pass = 'api-pass'): ConfigPagesLoaderServiceInterface {
    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturnMap([
      ['stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value', $user],
      ['stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value', $pass],
    ]);
    return $loader;
  }

  /**
   * Builds a cache that returns a token cache hit and misses everything else.
   */
  protected function buildCacheWithToken(string $token): CacheBackendInterface {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnCallback(function (string $key) use ($token) {
      if ($key === self::TOKEN_CACHE_KEY) {
        return $this->cacheItem($token);
      }
      return FALSE;
    });
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Cover images are only queued on production.
    putenv('AH_SITE_ENVIRONMENT=prod');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Ensure installer globals and environment never leak between tests.
    unset($GLOBALS['install_state']);
    putenv('AH_SITE_ENVIRONMENT');
    parent::tearDown();
  }

  // ---------------------------------------------------------------------------
  // getApiToken tests
  // ---------------------------------------------------------------------------

  /**
   * Tests get API token returns cached token.
   */
  public function testGetApiTokenReturnsCachedToken(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with(self::TOKEN_CACHE_KEY)
      ->willReturn($this->cacheItem('cached-token'));

    // HTTP client and config loader must not be called when cache hit.
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertSame('cached-token', $helper->getApiToken());
  }

  /**
   * Tests get API token returns false when user missing.
   */
  public function testGetApiTokenReturnsFalseWhenUserMissing(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturnMap([
      ['stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value', NULL],
      ['stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value', 'pass'],
    ]);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * Tests get API token returns false when password missing.
   */
  public function testGetApiTokenReturnsFalseWhenPasswordMissing(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturnMap([
      ['stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value', 'user'],
      ['stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value', NULL],
    ]);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * Tests get API token returns false when installer is running.
   */
  public function testGetApiTokenReturnsFalseWhenInstallerIsRunning(): void {
    // Simulate an in-progress installation.
    $GLOBALS['install_state'] = ['installation_finished' => FALSE];

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * Tests get API token returns false on request exception.
   */
  public function testGetApiTokenReturnsFalseOnRequestException(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException(new \RuntimeException('Connection refused'));

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * Tests get API token fetches token and caches it.
   */
  public function testGetApiTokenFetchesTokenAndCachesIt(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);
    $cache->expects($this->once())
      ->method('set')
      ->with(
        self::TOKEN_CACHE_KEY,
        'fresh-token',
        $this->greaterThan(time()),
      );

    $response = $this->buildResponse(['response' => ['token' => 'fresh-token']]);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('POST', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/sessions', $this->callback(function (array $opts) {
        return $opts['auth'] === ['api-user', 'api-pass']
          && ($opts['headers']['Content-Type'] ?? '') === 'application/json';
      }))
      ->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertSame('fresh-token', $helper->getApiToken());
  }

  // ---------------------------------------------------------------------------
  // queueCoverImages tests
  // ---------------------------------------------------------------------------

  /**
   * Tests queue cover images skips when no token.
   */
  public function testQueueCoverImagesSkipsWhenNoToken(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    // No credentials → getApiToken() returns false.
    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturn(NULL);

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(0);
    $queue->expects($this->never())->method('createItem');
    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $queueFactory, $cache);
    $helper->queueCoverImages(); // Must not throw.
  }

  /**
   * Tests queue cover images skips outside production.
   */
  public function testQueueCoverImagesSkipsOutsideProduction(): void {
    putenv('AH_SITE_ENVIRONMENT=dev');

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->expects($this->never())->method('get');

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $this->buildCacheWithToken('tok'));
    $helper->queueCoverImages();
  }

  /**
   * Tests queue cover images detects no records from response body.
   */
  public function testQueueCoverImagesDetectsNoRecordsFromResponseBody(): void {
    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(0);
    $queue->expects($this->never())->method('createItem');
    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    // Guzzle truncates the body in the message, so rely on the response.
    $errorResponse = $this->buildResponse(['messages' => [['message' => 'No records match the request', 'code' => '401']], 'response' => []]);
    $exception = new ServerException('Server error: 500 Internal Server Error', $this->createMock(RequestInterface::class), $errorResponse);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $this->buildCacheWithToken('tok'));
    $helper->queueCoverImages(); // Must not throw.
  }

  /**
   * Tests queue cover images sends limit in body.
   */
  public function testQueueCoverImagesSendsLimitInBody(): void {
    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(0);
    // The record without a recordId is ignored.
    $queue->expects($this->once())->method('createItem')->with('record-1');
    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('POST', PressHelper::COVERS_ENDPOINT, $this->callback(function (array $opts) {
        $body = json_decode($opts['body'], TRUE);
        return $body['limit'] === 100 && $body['query'] === [['flag' => '=x']];
      }))
      ->willReturn($this->buildResponse(['response' => ['data' => [['recordId' => 'record-1'], ['fieldData' => []]]]]));

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $this->buildCacheWithToken('tok'));
    $helper->queueCoverImages();
  }

  /**
   * Tests queue cover images skips when queue already has items.
   */
  public function testQueueCoverImagesSkipsWhenQueueAlreadyHasItems(): void {
    $cache = $this->buildCacheWithToken('tok');

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(5);
    $queue->expects($this->never())->method('createItem');

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->with('press_cover_downloader')->willReturn($queue);

    $client = $this->createMock(ClientInterface::class);
    // The HTTP request for covers must not be made.
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);
    $helper->queueCoverImages();
  }

  /**
   * Tests queue cover images creates item for each cover.
   */
  public function testQueueCoverImagesCreatesItemForEachCover(): void {
    $cache = $this->buildCacheWithToken('tok');

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(0);
    $queue->expects($this->exactly(2))
      ->method('createItem')
      ->willReturnCallback(function ($recordId) {
        $this->assertContains($recordId, ['record-1', 'record-2']);
      });

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $coversBody = json_encode([
      'response' => [
        'data' => [
          ['recordId' => 'record-1'],
          ['recordId' => 'record-2'],
        ],
      ],
    ]);
    $response = $this->buildResponse($coversBody);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('POST', $this->stringContains('Covers/_find'), $this->callback(function (array $opts) {
        return ($opts['headers']['Authorization'] ?? '') === 'Bearer tok';
      }))
      ->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);
    $helper->queueCoverImages();
  }

  /**
   * Tests queue cover images swallows no records exception.
   */
  public function testQueueCoverImagesSwallowsNoRecordsException(): void {
    $cache = $this->buildCacheWithToken('tok');

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(0);
    $queue->expects($this->never())->method('createItem');

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $request = $this->createMock(RequestInterface::class);
    $errorResponse = $this->createMock(ResponseInterface::class);
    $exception = new ServerException('No records match the request', $request, $errorResponse);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);
    $helper->queueCoverImages(); // Must not throw.
  }

  /**
   * Tests queue cover images rethrows other exceptions.
   */
  public function testQueueCoverImagesRethrowsOtherExceptions(): void {
    $cache = $this->buildCacheWithToken('tok');

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(0);

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $request = $this->createMock(RequestInterface::class);
    $errorResponse = $this->createMock(ResponseInterface::class);
    $exception = new ServerException('Internal server error', $request, $errorResponse);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);

    $this->expectException(ServerException::class);
    $helper->queueCoverImages();
  }

  // ---------------------------------------------------------------------------
  // getEbookData tests
  // ---------------------------------------------------------------------------

  /**
   * Builds a FileMaker find response from a list of fieldData arrays.
   */
  protected function buildFindResponse(array $fieldData): ResponseInterface {
    return $this->buildResponse([
      'response' => [
        'data' => array_map(fn($fields) => ['fieldData' => $fields], $fieldData),
      ],
    ]);
  }

  /**
   * Tests get ebook data groups records by work.
   */
  public function testGetEbookDataGroupsRecordsByWork(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('POST', PressHelper::EBOOK_RETAILERS_ENDPOINT, $this->callback(function (array $options) {
        $body = json_decode($options['body'], TRUE);
        return $options['headers']['Authorization'] === 'Bearer tok'
          && count($body['query']) === 3
          && $body['offset'] === 1;
      }))
      ->willReturn($this->buildFindResponse([
        ['work_id_number' => '00012', 'retailer' => 'Amazon', 'link' => ' https://amazon.example.com ', 'format_epub' => 'yes', 'format_pdf' => ''],
        ['work_id_number' => '12', 'retailer' => 'Kobo', 'link' => 'https://kobo.example.com', 'format_epub' => '', 'format_pdf' => 'yes'],
        ['work_id_number' => '34', 'retailer' => 'Bad', 'link' => 'not a url', 'format_epub' => '', 'format_pdf' => ''],
        ['work_id_number' => '56', 'retailer' => '', 'link' => '', 'format_epub' => 'yes', 'format_pdf' => ''],
        ['work_id_number' => '', 'retailer' => 'Orphan', 'link' => 'https://orphan.example.com', 'format_epub' => 'yes', 'format_pdf' => 'yes'],
      ]));

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $this->buildCacheWithToken('tok'));

    $this->assertSame([
      12 => [
        'retailers' => [
          ['title' => 'Amazon', 'uri' => 'https://amazon.example.com'],
          ['title' => 'Kobo', 'uri' => 'https://kobo.example.com'],
        ],
        'epub' => TRUE,
        'pdf' => TRUE,
      ],
      56 => ['epub' => TRUE, 'pdf' => FALSE],
    ], $helper->getEbookData());
  }

  /**
   * Tests get ebook data fetches all pages.
   */
  public function testGetEbookDataFetchesAllPages(): void {
    $full_page = array_fill(0, 10000, ['work_id_number' => '1', 'retailer' => 'A', 'link' => 'https://a.example.com']);
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->exactly(2))
      ->method('request')
      ->willReturnOnConsecutiveCalls(
        $this->buildFindResponse($full_page),
        $this->buildFindResponse([['work_id_number' => '2', 'retailer' => 'B', 'link' => 'https://b.example.com']]),
      );

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $this->buildCacheWithToken('tok'));
    $data = $helper->getEbookData();

    $this->assertCount(10000, $data[1]['retailers']);
    $this->assertCount(1, $data[2]['retailers']);
  }

  /**
   * Tests get ebook data returns empty when no records match.
   */
  public function testGetEbookDataReturnsEmptyWhenNoRecordsMatch(): void {
    $exception = new ServerException('No records match the request', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $this->buildCacheWithToken('tok'));
    $this->assertSame([], $helper->getEbookData());
  }

  /**
   * Tests get ebook data throws on request failure.
   */
  public function testGetEbookDataThrowsOnRequestFailure(): void {
    $exception = new ServerException('Internal server error', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $this->buildCacheWithToken('tok'));
    $this->expectException(ServerException::class);
    $helper->getEbookData();
  }

  /**
   * Tests get ebook data throws without token.
   */
  public function testGetEbookDataThrowsWithoutToken(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);
    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturn(NULL);

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $this->createMock(QueueFactory::class), $cache);
    $this->expectException(\RuntimeException::class);
    $helper->getEbookData();
  }

}
