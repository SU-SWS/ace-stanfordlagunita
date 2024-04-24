<?php

declare(strict_types=1);

namespace Drupal\Tests\supress_helper\Unit\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\supress_helper\EventSubscriber\SuPressEventSubscriber;
use Drupal\supress_helper\PressAwardInterface;
use Drupal\Tests\UnitTestCase;

/**
 *
 */
class SuPressEventSubscriberTest extends UnitTestCase {

  /**
   * @var \Drupal\supress_helper\EventSubscriber\SuPressEventSubscriber
   */
  protected $eventSubscriber;

  protected $migration = FALSE;

  protected function setUp(): void {
    parent::setUp();
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $migration_manager = $this->createMock(MigrationPluginManagerInterface::class);
    $migration_manager->method('createInstance')
      ->willReturnReference($this->migration);

    $this->eventSubscriber = new SuPressEventSubscriber($entity_type_manager, $migration_manager);
  }

  public function testEntityInsertEvent() {
    $fieldItem = $this->createMock(FieldItemListInterface::class);
    $fieldItem->method('count')->willReturn(1);
    $fieldItem->method('getString')->willReturn('321');

    $entity = $this->createMock(PressAwardInterface::class);
    $entity->method('get')->willReturn($fieldItem);
    $event = new EntityCreateEvent($entity);
    $this->assertNull($this->eventSubscriber->onEntityCreate($event));

    $id_map = $this->createMock(MigrateIdMapInterface::class);
    $id_map->method('setUpdate')->willReturn(TRUE);

    $this->migration = $this->createMock(MigrationInterface::class);
    $this->migration->method('getIdMap')->willReturn($id_map);
    $this->assertNull($this->eventSubscriber->onEntityCreate($event));

    $entity = $this->createMock(MediaInterface::class);
    $entity->method('bundle')->willReturn('image');
    $entity->method('get')->willReturn($fieldItem);
    $event = new EntityCreateEvent($entity);
    $this->assertNull($this->eventSubscriber->onEntityCreate($event));
  }

}