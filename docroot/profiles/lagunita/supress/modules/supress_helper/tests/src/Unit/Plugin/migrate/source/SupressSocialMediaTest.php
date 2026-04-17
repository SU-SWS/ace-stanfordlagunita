<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\Plugin\migrate\source;

use Drupal\supress_helper\Plugin\migrate\source\SupressSocialMedia;
use Drupal\supress_helper\PressHelperInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for the SupressSocialMedia migrate source plugin.
 *
 * @coversDefaultClass \Drupal\supress_helper\Plugin\migrate\source\SupressSocialMedia
 * @group supress_helper
 */
class SupressSocialMediaTest extends UnitTestCase {

  /**
   * Returns a plugin instance with mocked dependencies.
   */
  protected function buildPlugin(ClientInterface $client, PressHelperInterface $pressHelper, array $config = []): SupressSocialMedia {
    $migration = $this->createMock('\Drupal\migrate\Plugin\MigrationInterface');
    return new SupressSocialMedia($config, 'supress_social_media', [], $migration, $client, $pressHelper);
  }

  /**
   * Builds a PSR-7 response wrapping the given body content.
   */
  protected function buildResponse(string $body): ResponseInterface {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, $body);
    rewind($resource);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(new Stream($resource));
    return $response;
  }

  // ---------------------------------------------------------------------------
  // initializeIterator tests
  // ---------------------------------------------------------------------------

  /**
   * @covers ::initializeIterator
   */
  public function testInitializeIteratorFetchesAllPages(): void {
    $page1 = $this->buildResponse(json_encode([
      'response' => [
        'data' => [
          ['fieldData' => ['work_id_number' => 1, 'title' => 'Book A', 'url' => 'https://a.example.com', 'description' => '', 'source' => '']],
        ],
      ],
    ]));
    $page2 = $this->buildResponse(json_encode([
      'response' => [
        'data' => [
          ['fieldData' => ['work_id_number' => 2, 'title' => 'Book B', 'url' => 'https://b.example.com', 'description' => '', 'source' => '']],
        ],
      ],
    ]));

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->exactly(2))
      ->method('request')
      ->willReturnOnConsecutiveCalls($page1, $page2);

    $pressHelper = $this->createMock(PressHelperInterface::class);
    $pressHelper->method('getApiToken')->willReturn('test-token');
    $pressHelper->method('getMigrationUrls')
      ->with(SupressSocialMedia::ENDPOINT)
      ->willReturn([
        SupressSocialMedia::ENDPOINT . '?_limit=1000&_offset=1',
        SupressSocialMedia::ENDPOINT . '?_limit=1000&_offset=1001',
      ]);

    $plugin = $this->buildPlugin($client, $pressHelper);

    $method = new \ReflectionMethod($plugin, 'initializeIterator');
    $iterator = $method->invoke($plugin);

    $this->assertInstanceOf(\ArrayIterator::class, $iterator);
    $this->assertCount(2, $iterator);
  }

  /**
   * @covers ::initializeIterator
   */
  public function testInitializeIteratorCombinesRecordsAcrossPages(): void {
    // Both pages contain a record for work_id 1 — they should be merged into
    // a single item with concatenated HTML.
    $page1 = $this->buildResponse(json_encode([
      'response' => [
        'data' => [
          ['fieldData' => ['work_id_number' => 1, 'title' => 'Book A', 'url' => 'https://a.example.com', 'description' => 'First review.', 'source' => '']],
        ],
      ],
    ]));
    $page2 = $this->buildResponse(json_encode([
      'response' => [
        'data' => [
          ['fieldData' => ['work_id_number' => 1, 'title' => 'Book A second', 'url' => 'https://a2.example.com', 'description' => 'Second review.', 'source' => '']],
        ],
      ],
    ]));

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturnOnConsecutiveCalls($page1, $page2);

    $pressHelper = $this->createMock(PressHelperInterface::class);
    $pressHelper->method('getApiToken')->willReturn('test-token');
    $pressHelper->method('getMigrationUrls')->willReturn(['https://url1', 'https://url2']);

    $plugin = $this->buildPlugin($client, $pressHelper);

    $method = new \ReflectionMethod($plugin, 'initializeIterator');
    $iterator = $method->invoke($plugin);

    $this->assertCount(1, $iterator);
    $item = $iterator->current();
    $this->assertStringContainsString('First review.', $item['html']);
    $this->assertStringContainsString('Second review.', $item['html']);
  }

  /**
   * @covers ::initializeIterator
   */
  public function testInitializeIteratorPassesTokenInHeader(): void {
    $response = $this->buildResponse(json_encode(['response' => ['data' => []]]));

    $client = $this->createMock(ClientInterface::class);
    $client->expects($this->once())
      ->method('request')
      ->with('GET', 'https://paged-url', $this->callback(function (array $opts) {
        return ($opts['headers']['Authorization'] ?? '') === 'Bearer my-token';
      }))
      ->willReturn($response);

    $pressHelper = $this->createMock(PressHelperInterface::class);
    $pressHelper->method('getApiToken')->willReturn('my-token');
    $pressHelper->method('getMigrationUrls')->willReturn(['https://paged-url']);

    $plugin = $this->buildPlugin($client, $pressHelper);

    $method = new \ReflectionMethod($plugin, 'initializeIterator');
    $method->invoke($plugin);
  }

  /**
   * @covers ::initializeIterator
   */
  public function testInitializeIteratorUsesConfiguredUrl(): void {
    $response = $this->buildResponse(json_encode(['response' => ['data' => []]]));

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturn($response);

    $pressHelper = $this->createMock(PressHelperInterface::class);
    $pressHelper->method('getApiToken')->willReturn('tok');
    $pressHelper->expects($this->once())
      ->method('getMigrationUrls')
      ->with('https://custom.example.com/records')
      ->willReturn(['https://custom.example.com/records?_limit=1000&_offset=1']);

    $plugin = $this->buildPlugin($client, $pressHelper, ['url' => 'https://custom.example.com/records']);

    $method = new \ReflectionMethod($plugin, 'initializeIterator');
    $method->invoke($plugin);
  }

  // ---------------------------------------------------------------------------
  // processRecords tests
  // ---------------------------------------------------------------------------

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsEmptyInput(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $this->assertSame([], $plugin->processRecords([]));
  }

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsSkipsRecordsMissingTitleOrWorkId(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $records = [
      ['fieldData' => ['title' => 'Only Title', 'url' => 'https://example.com']],
      ['fieldData' => ['work_id_number' => 42, 'url' => 'https://example.com']],
    ];

    $this->assertSame([], $plugin->processRecords($records));
  }

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsSingleRecord(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $records = [
      [
        'fieldData' => [
          'work_id_number' => 7,
          'title' => 'My Book',
          'url' => 'https://example.com/book',
          'description' => '<p>A great read.</p>',
          'source' => 'The Times',
        ],
      ],
    ];

    $result = $plugin->processRecords($records);

    $this->assertCount(1, $result);
    $this->assertSame(7, $result[0]['workId']);
    $this->assertStringContainsString('<a href="https://example.com/book">My Book</a>', $result[0]['html']);
    $this->assertStringContainsString('A great read.', $result[0]['html']);
    $this->assertStringContainsString('&mdash;The Times', $result[0]['html']);
  }

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsGroupsByWorkId(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $records = [
      ['fieldData' => ['work_id_number' => 1, 'title' => 'Book A', 'url' => 'https://a.example.com', 'description' => '', 'source' => '']],
      ['fieldData' => ['work_id_number' => 2, 'title' => 'Book B', 'url' => 'https://b.example.com', 'description' => '', 'source' => '']],
      ['fieldData' => ['work_id_number' => 1, 'title' => 'Book A (review)', 'url' => 'https://c.example.com', 'description' => '', 'source' => '']],
    ];

    $result = $plugin->processRecords($records);

    $this->assertCount(2, $result);
    $byId = array_column($result, NULL, 'workId');
    $this->assertArrayHasKey(1, $byId);
    $this->assertArrayHasKey(2, $byId);
    $this->assertStringContainsString('Book A (review)', $byId[1]['html']);
  }

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsAddsIframeTitleWhenMissing(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $records = [
      [
        'fieldData' => [
          'work_id_number' => 5,
          'title' => 'Video <Book>',
          'url' => 'https://example.com',
          'description' => '<iframe src="https://vid.example.com" width="560"></iframe>',
          'source' => '',
        ],
      ],
    ];

    $result = $plugin->processRecords($records);
    $this->assertStringContainsString('title="Video &lt;Book&gt;"', $result[0]['html']);
  }

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsPreservesExistingIframeTitle(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $records = [
      [
        'fieldData' => [
          'work_id_number' => 3,
          'title' => 'My Video',
          'url' => 'https://example.com',
          'description' => '<iframe title="Custom Title" src="https://vid.example.com"></iframe>',
          'source' => '',
        ],
      ],
    ];

    $result = $plugin->processRecords($records);
    $this->assertStringContainsString('title="Custom Title"', $result[0]['html']);
    $this->assertSame(1, substr_count($result[0]['html'], 'title='));
  }

  /**
   * @covers ::processRecords
   */
  public function testProcessRecordsOmitsEmptyParagraph(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);

    $records = [
      [
        'fieldData' => [
          'work_id_number' => 9,
          'title' => 'No Extra',
          'url' => 'https://example.com',
          'description' => '',
          'source' => '',
        ],
      ],
    ];

    $result = $plugin->processRecords($records);
    $this->assertStringNotContainsString('<p></p>', $result[0]['html']);
  }

  // ---------------------------------------------------------------------------
  // getIds / fields / __toString tests
  // ---------------------------------------------------------------------------

  /**
   * @covers ::getIds
   */
  public function testGetIds(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);
    $this->assertSame(['workId' => ['type' => 'integer']], $plugin->getIds());
  }

  /**
   * @covers ::fields
   */
  public function testFields(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);
    $fields = $plugin->fields();
    $this->assertArrayHasKey('workId', $fields);
    $this->assertArrayHasKey('html', $fields);
  }

  /**
   * @covers ::__toString
   */
  public function testToStringDefaultsToEndpointConstant(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper);
    $this->assertSame(SupressSocialMedia::ENDPOINT, (string) $plugin);
  }

  /**
   * @covers ::__toString
   */
  public function testToStringUsesConfiguredUrl(): void {
    $client = $this->createMock(ClientInterface::class);
    $pressHelper = $this->createMock(PressHelperInterface::class);
    $plugin = $this->buildPlugin($client, $pressHelper, ['url' => 'https://custom.example.com/records']);
    $this->assertSame('https://custom.example.com/records', (string) $plugin);
  }

}
