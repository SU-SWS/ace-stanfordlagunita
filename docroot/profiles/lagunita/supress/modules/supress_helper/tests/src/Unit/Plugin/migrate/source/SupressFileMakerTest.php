<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\Plugin\migrate\source;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\supress_helper\Plugin\migrate\source\SupressFileMaker;
use Drupal\supress_helper\PressHelperInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for the SupressFileMaker migrate source plugin.
 *
 * @coversDefaultClass \Drupal\supress_helper\Plugin\migrate\source\SupressFileMaker
 * @group supress_helper
 */
class SupressFileMakerTest extends UnitTestCase {

  const URL = 'https://example.com/layouts/Web/records';

  /**
   * Returns a plugin instance with mocked dependencies.
   */
  protected function buildPlugin(ClientInterface $client, string|false $token = 'tok', ?DataParserPluginManager $parserManager = NULL, array $urls = [self::URL]): SupressFileMaker {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('getIdMap')->willReturn($this->createMock(MigrateIdMapInterface::class));
    $helper = $this->createMock(PressHelperInterface::class);
    $helper->method('getApiToken')->willReturn($token);
    $config = [
      'urls' => $urls,
      'fields' => [],
      'ids' => ['work_id_number' => ['type' => 'integer']],
      'data_parser_plugin' => 'json',
    ];
    return new SupressFileMaker($config, 'supress_filemaker', [], $migration, $parserManager ?? $this->createMock(DataParserPluginManager::class), $helper, $client);
  }

  /**
   * Builds a client that reports the given total record count.
   */
  protected function buildCountClient(int $total): ClientInterface {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, json_encode(['response' => ['dataInfo' => ['totalRecordCount' => $total]]]));
    rewind($resource);
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(new Stream($resource));

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('GET', self::URL, $this->callback(fn(array $options) => $options['query'] === ['_limit' => 1]
        && $options['headers']['Authorization'] === 'Bearer tok'))
      ->willReturn($response);
    return $client;
  }

  /**
   * @covers ::__construct
   */
  public function testConstructorMakesNoApiRequests(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');
    $plugin = $this->buildPlugin($client);
    $this->assertSame(self::URL, (string) $plugin);
  }

  /**
   * @covers ::getSourceUrls
   * @covers ::getDataParserPlugin
   * @covers ::expandUrls
   * @covers ::getPagedUrls
   * @covers ::getRecordCount
   */
  public function testUrlsExpandedOnceAndPassedToParser(): void {
    $paged = [
      self::URL . '?_limit=5000&_offset=1',
      self::URL . '?_limit=5000&_offset=5001',
      self::URL . '?_limit=5000&_offset=10001',
    ];

    $parser_manager = $this->createMock(DataParserPluginManager::class);
    $parser_manager->expects($this->once())
      ->method('createInstance')
      ->with('json', $this->callback(fn(array $config) => $config['urls'] === $paged))
      ->willReturn($this->createMock(DataParserPluginInterface::class));

    $plugin = $this->buildPlugin($this->buildCountClient(10001), 'tok', $parser_manager);
    $this->assertSame($paged, $plugin->getSourceUrls());
    $plugin->getDataParserPlugin();
    $this->assertSame($paged, $plugin->getSourceUrls());
  }

  /**
   * @covers ::getPagedUrls
   */
  public function testExactPageBoundary(): void {
    $plugin = $this->buildPlugin($this->buildCountClient(5000));
    $this->assertSame([self::URL . '?_limit=5000&_offset=1'], $plugin->getSourceUrls());
  }

  /**
   * @covers ::expandUrls
   */
  public function testThrowsWhenNoRecords(): void {
    $parser_manager = $this->createMock(DataParserPluginManager::class);
    $parser_manager->expects($this->never())->method('createInstance');

    $this->expectException(MigrateException::class);
    $this->buildPlugin($this->buildCountClient(0), 'tok', $parser_manager)->getDataParserPlugin();
  }

  /**
   * @covers ::getPagedUrls
   */
  public function testThrowsWithoutToken(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $this->expectException(MigrateException::class);
    $this->buildPlugin($client, FALSE)->getSourceUrls();
  }

  /**
   * @covers ::getRecordCount
   */
  public function testRequestFailureIsNotSwallowed(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willThrowException(new ConnectException('Timed out', $this->createMock(RequestInterface::class)));

    $this->expectException(ConnectException::class);
    $this->buildPlugin($client)->getSourceUrls();
  }

  /**
   * @covers ::expandUrls
   */
  public function testThrowsWhenNoUrlConfigured(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->never())->method('request');

    $this->expectException(MigrateException::class);
    $this->buildPlugin($client, 'tok', NULL, [])->getSourceUrls();
  }

}
