<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\supress_helper\PressHelper;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for the PressHelper service.
 *
 * @coversDefaultClass \Drupal\supress_helper\PressHelper
 * @group supress_helper
 */
class PressHelperTest extends UnitTestCase {

  /**
   * Cache key used by PressHelper for the API token.
   */
  const TOKEN_CACHE_KEY = 'filemaker_api_token';

  /**
   * Builds a PressHelper with the given dependencies.
   */
  protected function buildHelper(
    GuzzleClient $client,
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
   * Returns the record-count cache key for a given URL, matching PressHelper's
   * own key derivation logic.
   */
  protected function recordCountCacheKey(string $url): string {
    return 'press-record-count:' . substr(md5($url), 0, 5);
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
  protected function tearDown(): void {
    // Ensure installer globals never leak between tests.
    unset($GLOBALS['install_state']);
    parent::tearDown();
  }

  // ---------------------------------------------------------------------------
  // getApiToken tests
  // ---------------------------------------------------------------------------

  /**
   * @covers ::getApiToken
   */
  public function testGetApiTokenReturnsCachedToken(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with(self::TOKEN_CACHE_KEY)
      ->willReturn($this->cacheItem('cached-token'));

    // HTTP client and config loader must not be called when cache hit.
    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertSame('cached-token', $helper->getApiToken());
  }

  /**
   * @covers ::getApiToken
   */
  public function testGetApiTokenReturnsFalseWhenUserMissing(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturnMap([
      ['stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value', NULL],
      ['stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value', 'pass'],
    ]);

    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * @covers ::getApiToken
   */
  public function testGetApiTokenReturnsFalseWhenPasswordMissing(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturnMap([
      ['stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value', 'user'],
      ['stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value', NULL],
    ]);

    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * @covers ::getApiToken
   */
  public function testGetApiTokenReturnsFalseWhenInstallerIsRunning(): void {
    // Simulate an in-progress installation.
    $GLOBALS['install_state'] = ['installation_finished' => FALSE];

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * @covers ::getApiToken
   */
  public function testGetApiTokenReturnsFalseOnRequestException(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willThrowException(new \RuntimeException('Connection refused'));

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertFalse($helper->getApiToken());
  }

  /**
   * @covers ::getApiToken
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

    $client = $this->createMock(GuzzleClient::class);
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
  // getMigrationUrls tests
  // ---------------------------------------------------------------------------

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsReturnsEmptyArrayWhenZeroRecords(): void {
    $url = 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/SocialMedia/records';
    $cache = $this->buildCacheWithToken('tok');
    // Record count cache miss; HTTP returns 0 total records.
    $response = $this->buildResponse(['response' => ['dataInfo' => ['totalRecordCount' => 0]]]);

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertSame([], $helper->getMigrationUrls($url));
  }

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsReturnsSingleUrlForSmallCount(): void {
    $url = 'https://example.com/records';
    $cache = $this->buildCacheWithToken('tok');
    $response = $this->buildResponse(['response' => ['dataInfo' => ['totalRecordCount' => 500]]]);

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $urls = $helper->getMigrationUrls($url);

    $this->assertCount(1, $urls);
    $this->assertStringContainsString('_limit=1000', $urls[0]);
    $this->assertStringContainsString('_offset=1', $urls[0]);
    $this->assertStringStartsWith($url, $urls[0]);
  }

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsPaginatesCorrectly(): void {
    $url = 'https://example.com/records';
    $cache = $this->buildCacheWithToken('tok');
    // 2500 records → 3 pages: offsets 1, 1001, 2001.
    $response = $this->buildResponse(['response' => ['dataInfo' => ['totalRecordCount' => 2500]]]);

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $urls = $helper->getMigrationUrls($url);

    $this->assertCount(3, $urls);
    $this->assertStringContainsString('_offset=1', $urls[0]);
    $this->assertStringContainsString('_offset=1001', $urls[1]);
    $this->assertStringContainsString('_offset=2001', $urls[2]);
  }

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsGeneratesExactPageBoundary(): void {
    $url = 'https://example.com/records';
    $cache = $this->buildCacheWithToken('tok');
    // Exactly 1000 records → exactly 1 page.
    $response = $this->buildResponse(['response' => ['dataInfo' => ['totalRecordCount' => 1000]]]);

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $this->assertCount(1, $helper->getMigrationUrls($url));
  }

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsUsesCachedRecordCount(): void {
    $url = 'https://example.com/records';
    $cacheKey = $this->recordCountCacheKey($url);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnCallback(function (string $key) use ($cacheKey) {
      if ($key === $cacheKey) {
        return $this->cacheItem(750);
      }
      return FALSE;
    });

    // HTTP client must not be called at all when the count is cached.
    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $urls = $helper->getMigrationUrls($url);

    $this->assertCount(1, $urls);
  }

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsCachesRecordCount(): void {
    $url = 'https://example.com/records';
    $cacheKey = $this->recordCountCacheKey($url);

    $cache = $this->buildCacheWithToken('tok');
    $cache->expects($this->once())
      ->method('set')
      ->with($cacheKey, 300, $this->greaterThan(time()), ['press-record-count']);

    $response = $this->buildResponse(['response' => ['dataInfo' => ['totalRecordCount' => 300]]]);

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $helper->getMigrationUrls($url);
  }

  /**
   * @covers ::getMigrationUrls
   */
  public function testGetMigrationUrlsPassesTokenInRecordCountRequest(): void {
    $url = 'https://example.com/records';
    $cache = $this->buildCacheWithToken('my-token');

    $response = $this->buildResponse(['response' => ['dataInfo' => ['totalRecordCount' => 1]]]);

    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->once())
      ->method('request')
      ->with('GET', $url, $this->callback(function (array $opts) {
        return ($opts['headers']['Authorization'] ?? '') === 'Bearer my-token';
      }))
      ->willReturn($response);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $this->createMock(QueueFactory::class), $cache);

    $helper->getMigrationUrls($url);
  }

  // ---------------------------------------------------------------------------
  // queueCoverImages tests
  // ---------------------------------------------------------------------------

  /**
   * @covers ::queueCoverImages
   */
  public function testQueueCoverImagesSkipsWhenNoToken(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    // No credentials → getApiToken() returns false.
    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('getValue')->willReturn(NULL);

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->expects($this->never())->method('get');

    $client = $this->createMock(GuzzleClient::class);
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $loader, $queueFactory, $cache);
    $helper->queueCoverImages(); // Must not throw.
  }

  /**
   * @covers ::queueCoverImages
   */
  public function testQueueCoverImagesSkipsWhenQueueAlreadyHasItems(): void {
    $cache = $this->buildCacheWithToken('tok');

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('numberOfItems')->willReturn(5);
    $queue->expects($this->never())->method('createItem');

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->with('press_cover_downloader')->willReturn($queue);

    $client = $this->createMock(GuzzleClient::class);
    // The HTTP request for covers must not be made.
    $client->expects($this->never())->method('request');

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);
    $helper->queueCoverImages();
  }

  /**
   * @covers ::queueCoverImages
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

    $client = $this->createMock(GuzzleClient::class);
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
   * @covers ::queueCoverImages
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

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);
    $helper->queueCoverImages(); // Must not throw.
  }

  /**
   * @covers ::queueCoverImages
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

    $client = $this->createMock(GuzzleClient::class);
    $client->method('request')->willThrowException($exception);

    $helper = $this->buildHelper($client, $this->buildConfigLoader(), $queueFactory, $cache);

    $this->expectException(ServerException::class);
    $helper->queueCoverImages();
  }

}
