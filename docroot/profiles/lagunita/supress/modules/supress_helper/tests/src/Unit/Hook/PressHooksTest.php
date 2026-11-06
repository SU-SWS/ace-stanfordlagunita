<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\Hook;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\supress_helper\Hook\PressHooks;
use Drupal\supress_helper\PressHelperInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @coversDefaultClass \Drupal\supress_helper\Hook\PressHooks
 */
class PressHooksTest extends UnitTestCase {

  /**
   * Migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migrationManager;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * Press helper service.
   *
   * @var \Drupal\supress_helper\PressHelperInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pressHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationManager = $this->createMock(MigrationPluginManagerInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->pressHelper = $this->createMock(PressHelperInterface::class);
  }

  /**
   * Creating an award or cover image flags its book for re-import.
   */
  public function testEntityCreateFlagsBook(): void {
    $id_map = $this->createMock(MigrateIdMapInterface::class);
    $id_map->expects($this->exactly(2))
      ->method('setUpdate')
      ->with(['work_id_number' => '321']);
    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('getIdMap')->willReturn($id_map);
    $this->migrationManager->method('createInstance')
      ->with('sup_import_books')
      ->willReturn($migration);

    $hooks = $this->getHooks();
    $hooks->pressCreate($this->getEntity(ContentEntityInterface::class, 'press'));
    $hooks->pressCreate($this->getEntity(MediaInterface::class, 'media', 'image'));
    // Other media bundles and entity types are ignored.
    $hooks->pressCreate($this->getEntity(MediaInterface::class, 'media', 'file'));
    $hooks->pressCreate($this->getEntity(ContentEntityInterface::class, 'node'));
  }

  /**
   * Entity creation still works when the book migration doesn't exist.
   */
  public function testEntityCreateWithoutMigration(): void {
    $this->migrationManager->method('createInstance')
      ->willThrowException(new PluginNotFoundException('sup_import_books'));
    $this->getHooks()->pressCreate($this->getEntity(ContentEntityInterface::class, 'press'));
    $this->addToAssertionCount(1);
  }

  /**
   * Protected files are hidden from anonymous users outside of GraphQL.
   */
  public function testMediaAccess(): void {
    $anonymous = $this->createMock(AccountInterface::class);
    $anonymous->method('isAnonymous')->willReturn(TRUE);
    $authenticated = $this->createMock(AccountInterface::class);
    $protected = $this->getEntity(MediaInterface::class, 'media', 'sup_protected_file');
    $image = $this->getEntity(MediaInterface::class, 'media', 'image');

    $this->routeMatch->method('getRouteName')
      ->willReturnOnConsecutiveCalls('entity.media.canonical', 'entity.media.canonical', 'entity.media.canonical', 'graphql.query.graphql_compose_server');
    $hooks = $this->getHooks();

    $this->assertTrue($hooks->mediaAccess($protected, 'view', $anonymous)->isForbidden());
    $this->assertFalse($hooks->mediaAccess($protected, 'view', $authenticated)->isForbidden());
    $this->assertFalse($hooks->mediaAccess($image, 'view', $anonymous)->isForbidden());
    $this->assertFalse($hooks->mediaAccess($protected, 'view', $anonymous)->isForbidden());
  }

  /**
   * The banner paragraph type can't be created.
   */
  public function testEntityCreateAccess(): void {
    $account = $this->createMock(AccountInterface::class);
    $hooks = $this->getHooks();
    $this->assertTrue($hooks->entityCreateAccess($account, [], 'stanford_banner')->isForbidden());
    $this->assertFalse($hooks->entityCreateAccess($account, [], 'stanford_card')->isForbidden());
    $this->assertFalse($hooks->entityCreateAccess($account, [], NULL)->isForbidden());
  }

  /**
   * Cron queues the cover image downloads.
   */
  public function testCron(): void {
    $this->pressHelper->expects($this->once())->method('queueCoverImages');
    $this->getHooks()->cron();
  }

  /**
   * Book vocabularies are suggested for the book view arguments.
   */
  public function testViewfieldVocabs(): void {
    $hooks = $this->getHooks();
    $vocabs = [];
    $hooks->viewfieldArgVocabs($vocabs, ['view' => 'other']);
    $this->assertEmpty($vocabs);

    $hooks->viewfieldArgVocabs($vocabs, ['view' => 'sup_books']);
    $this->assertEquals(['sup_book_subjects', 'sup_series', 'sup_book_tags', 'sup_imprints'], $vocabs);
  }

  /**
   * Ancillary weights are set from the title when empty.
   */
  #[DataProvider('ancillaryWeightProvider')]
  public function testAncillaryWeight(string $bundle, string $title, bool $empty, ?int $expected): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn($empty);

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn($bundle);
    $node->method('label')->willReturn($title);
    $node->method('hasField')->willReturn(TRUE);
    $node->method('get')->willReturn($field);

    if ($expected === NULL) {
      $node->expects($this->never())->method('set');
    }
    else {
      $node->expects($this->once())->method('set')->with('sup_ancillary_weight', $expected);
    }
    $this->getHooks()->nodePresave($node);
  }

  /**
   * Data provider for ::testAncillaryWeight().
   */
  public static function ancillaryWeightProvider(): array {
    return [
      ['sup_book_ancillary', 'Table of Contents', TRUE, 1],
      ['sup_book_ancillary', 'Contents', TRUE, 1],
      ['sup_book_ancillary', 'Foreword', TRUE, 5],
      ['sup_book_ancillary', 'Forword', TRUE, 5],
      ['sup_book_ancillary', 'Preface to the Second Edition', TRUE, 10],
      ['sup_book_ancillary', 'Acknowledgements', TRUE, 15],
      ['sup_book_ancillary', 'Acknowledgments', TRUE, 15],
      ['sup_book_ancillary', 'INTRODUCTION', TRUE, 20],
      ['sup_book_ancillary', 'Introduction Excerpt', TRUE, 20],
      ['sup_book_ancillary', 'Translator Introduction', TRUE, 20],
      ['sup_book_ancillary', 'Prologue', TRUE, 25],
      ['sup_book_ancillary', 'Epilogue', TRUE, 85],
      ['sup_book_ancillary', 'Afterword', TRUE, 90],
      ['sup_book_ancillary', 'Appendix A: Data', TRUE, 95],
      ['sup_book_ancillary', 'Chapter 1', TRUE, 50],
      ['sup_book_ancillary', 'Introduction', FALSE, NULL],
      ['stanford_page', 'Introduction', TRUE, NULL],
    ];
  }

  /**
   * Get the hooks class.
   */
  protected function getHooks(): PressHooks {
    return new PressHooks(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->routeMatch,
      $this->createMock(ModuleExtensionList::class),
      $this->pressHelper,
      $this->migrationManager,
    );
  }

  /**
   * Get a mock entity whose fields contain the work id 321.
   */
  protected function getEntity(string $class, string $entity_type, string $bundle = ''): ContentEntityInterface {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('count')->willReturn(1);
    $field->method('getString')->willReturn('321');

    $entity = $this->createMock($class);
    $entity->method('getEntityTypeId')->willReturn($entity_type);
    $entity->method('bundle')->willReturn($bundle);
    $entity->method('get')->willReturn($field);
    return $entity;
  }

}
