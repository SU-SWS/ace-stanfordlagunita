<?php

declare(strict_types=1);

namespace Drupal\supress_helper\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\supress_helper\PressAwardInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Entity event subscriber.
 */
final class SuPressEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a SuPressEventSubscriber object.
   */
  public function __construct(protected readonly EntityTypeManagerInterface $entityTypeManager, protected readonly MigrationPluginManagerInterface $migrationManager) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EntityHookEvents::ENTITY_CREATE => ['onEntityCreate'],
    ];
  }

  /**
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent $event
   *   Triggered Event.
   */
  public function onEntityCreate(EntityCreateEvent $event) {
    $entity = $event->getEntity();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationManager->createInstance('sup_import_books');
    $id_map = $migration->getIdMap();

    // If an award is created (only from the importer), set the node that
    // relates to the award to be updated on the next import.
    if ($entity instanceof PressAwardInterface) {
      $id_map->setUpdate(['work_id_number' => $entity->get('sup_work_id')->getString()]);
    }

    // If an image is created , set the node that relates to the award to be
    // updated on the next import.
    if (
      $entity instanceof MediaInterface &&
      $entity->bundle() == 'image' &&
      $entity->get('sup_book_work_id')->count()
    ) {
      $id_map->setUpdate(['work_id_number' => $entity->get('sup_book_work_id')->getString()]);
    }
  }

}
