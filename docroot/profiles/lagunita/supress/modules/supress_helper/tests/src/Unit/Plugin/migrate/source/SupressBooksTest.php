<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\Plugin\migrate\source;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\supress_helper\Plugin\migrate\source\SupressBooks;
use Drupal\supress_helper\PressHelperInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

/**
 * Unit tests for the SupressBooks migrate source plugin.
 *
 * @coversDefaultClass \Drupal\supress_helper\Plugin\migrate\source\SupressBooks
 * @group supress_helper
 */
class SupressBooksTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('invokeAll')->willReturn([]);
    $container = new ContainerBuilder();
    $container->set('module_handler', $module_handler);
    \Drupal::setContainer($container);
  }

  /**
   * Returns a plugin instance with mocked dependencies.
   */
  protected function buildPlugin(PressHelperInterface $pressHelper): SupressBooks {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('id')->willReturn('sup_import_books');
    $migration->method('getIdMap')->willReturn($this->createMock(MigrateIdMapInterface::class));
    $config = [
      'urls' => ['https://example.com/records'],
      'fields' => [],
      'ids' => ['work_id_number' => ['type' => 'integer']],
    ];
    return new SupressBooks($config, 'supress_books', [], $migration, $this->createMock(DataParserPluginManager::class), $pressHelper, $this->createMock(ClientInterface::class));
  }

  /**
   * Builds a row for the given work id.
   */
  protected function buildRow(int|string $workId): Row {
    return new Row([
      'work_id_number' => $workId,
      'current_feed_url' => 'https://example.com/records?_offset=1001',
    ], ['work_id_number' => ['type' => 'integer']]);
  }

  /**
   * @covers ::prepareRow
   * @covers ::getEbookData
   */
  public function testPrepareRowAddsEbookData(): void {
    $helper = $this->createMock(PressHelperInterface::class);
    // Fetched once, even across multiple rows.
    $helper->expects($this->once())->method('getEbookData')->willReturn([
      12 => [
        'retailers' => [['title' => 'Amazon', 'uri' => 'https://amazon.example.com']],
        'epub' => TRUE,
        'pdf' => FALSE,
      ],
    ]);
    $plugin = $this->buildPlugin($helper);

    // Brand-new row with leading zeros and no id map entry.
    $row = $this->buildRow('00012');
    $this->assertTrue($plugin->prepareRow($row));
    $this->assertSame([['title' => 'Amazon', 'uri' => 'https://amazon.example.com']], $row->getSourceProperty('ebook_retailers'));
    $this->assertTrue($row->getSourceProperty('epub'));
    $this->assertFalse($row->getSourceProperty('pdf'));
    $this->assertNull($row->getSourceProperty('current_feed_url'));

    $row = $this->buildRow(99);
    $this->assertTrue($plugin->prepareRow($row));
    $this->assertSame([], $row->getSourceProperty('ebook_retailers'));
    $this->assertNull($row->getSourceProperty('epub'));
    $this->assertNull($row->getSourceProperty('pdf'));
  }

  /**
   * @covers ::prepareRow
   * @covers ::getEbookData
   */
  public function testPrepareRowFailsWhenEbookDataUnavailable(): void {
    $helper = $this->createMock(PressHelperInterface::class);
    $helper->method('getEbookData')->willThrowException(new \RuntimeException('API down'));
    $plugin = $this->buildPlugin($helper);

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('API down');
    $plugin->prepareRow($this->buildRow(12));
  }

}
