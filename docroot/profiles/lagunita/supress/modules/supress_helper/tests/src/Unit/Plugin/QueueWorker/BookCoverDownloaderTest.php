<?php

namespace Drupal\Tests\supress_helper\Unit\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\supress_helper\Plugin\QueueWorker\BookCoverDownloader;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Psr7\Stream;

/**
 * @coversDefaultClass \Drupal\supress_helper\Plugin\QueueWorker\BookCoverDownloader
 */
class BookCoverDownloaderTest extends UnitTestCase {

  protected $client;

  protected $configPageLoader;

  protected $fileSystem;

  protected $entityTypeManager;

  protected function setUp(): void {
    parent::setUp();
    $this->client = $this->createMock('\GuzzleHttp\ClientInterface');
    $this->configPageLoader = $this->createMock('\Drupal\config_pages\ConfigPagesLoaderServiceInterface');
    $this->fileSystem = $this->createMock('\Drupal\Core\File\FileSystemInterface');
    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
  }

  /**
   * Test the downloader.
   */
  public function testBookCoverDownloader() {
    $this->configPageLoader->method('getValue')->willReturn('foo');
    $this->client->method('request')
      ->willReturnCallback([$this, 'getGuzzleResponse']);

    $entity_query = $this->createMock('\Drupal\Core\Entity\Query\QueryInterface');
    $entity_query->method('accessCheck')->willReturnSelf();
    $entity_query->method('condition')->willReturnSelf();

    $entity = $this->createMock('\Drupal\file\FileInterface');
    $entity->method('id')->willReturn(543);

    $entity_storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage->method('create')->willReturn($entity);
    $entity_storage->method('getQuery')->willReturn($entity_query);
    $this->entityTypeManager->method('getStorage')->wilLReturn($entity_storage);

    $worker = BookCoverDownloader::create($this->getDrupalContainer(), [], '', []);
    $this->assertEquals(543, $worker->processItem(123));
  }

  public function getGuzzleResponse($method, $url, $options) {
    $body_content = ['response' => ['token' => 'foobar']];
    if ($method == 'GET') {
      $body_content = [
        'response' => [
          'data' => [
            [
              'fieldData' => [
                'image' => '',
                'work_id_number' => 321,
              ],
              'recordId' => 123,
            ],
          ],
        ],
      ];
    }

    $response = $this->createMock('\Psr\Http\Message\ResponseInterface');
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, json_encode($body_content));
    rewind($resource);
    $body = new Stream($resource);
    $response->method('getBody')->willReturnReference($body);

    return $response;
  }

  protected function getDrupalContainer() {
    $container = new ContainerBuilder();
    $container->set('http_client', $this->client);
    $container->set('file_system', $this->fileSystem);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('config_pages.loader', $this->configPageLoader);
    return $container;
  }

}