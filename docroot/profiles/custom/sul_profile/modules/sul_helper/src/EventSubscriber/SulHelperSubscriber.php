<?php

namespace Drupal\sul_helper\EventSubscriber;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SUL Helper event subscriber.
 */
class SulHelperSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutParagraphsAllowedTypesEvent::EVENT_NAME => 'layoutParagraphsAllowedTypes',
    ];
  }

  /**
   * Adjust the paragraphs allowed in the layout paragraphs widget.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent $event
   *   Layout paragraphs event.
   */
  public function layoutParagraphsAllowedTypes(LayoutParagraphsAllowedTypesEvent $event): void {
    $parent_component = $event->getLayout()
      ->getComponentByUuid($event->getParentUuid());

    // If adding a new layout, it won't have a parent.
    if ($parent_component) {

      $layout_settings = $parent_component->getSettings();
      if ($layout_settings['layout'] != 'sul_helper_1_column') {
        $types = $event->getTypes();
        unset($types['stanford_banner'], $types['stanford_gallery']);
        $event->setTypes($types);
      }
    }
  }

}
