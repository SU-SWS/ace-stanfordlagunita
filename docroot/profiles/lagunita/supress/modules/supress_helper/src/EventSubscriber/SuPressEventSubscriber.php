<?php

declare(strict_types=1);

namespace Drupal\supress_helper\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityEvents;
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
      EntityEvents::ENTITY_ACTION => ['onNextEntityAction', 10],
      EntityHookEvents::ENTITY_CREATE => ['onEntityCreate'],
    ];
  }

  /**
   * Change the entity url if the entity is a price object.
   *
   * @param \Drupal\next\Event\EntityActionEvent $event
   *   Next entity event.
   */
  public function onNextEntityAction(EntityActionEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() == 'presss') {
      $bundle = $entity->bundle();
      $uuid = $entity->uuid();
      $event->setEntityUrl("/tags/$bundle:$uuid");
    }
  }

  /**
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent $event
   *   Triggered Event.
   */
  public function onEntityCreate(EntityCreateEvent $event) {
    $entity = $event->getEntity();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationManager->createInstance('sup_import_books');
    if (!$migration) {
      return;
    }

    // If an award is created (only from the importer), set the node that
    // relates to the award to be updated on the next import.
    if ($entity->getEntityTypeId() == 'press') {
      $migration->getIdMap()->setUpdate([
        'work_id_number' => $entity->get('work_id')->getString(),
      ]);
    }

    // If an image is created , set the node that relates to the award to be
    // updated on the next import.
    if (
      $entity instanceof MediaInterface &&
      $entity->bundle() == 'image' &&
      $entity->get('sup_book_work_id')->count()
    ) {
      $migration->getIdMap()->setUpdate([
        'work_id_number' => $entity->get('sup_book_work_id')->getString(),
      ]);
    }
  }

}
