<?php

namespace Drupal\sul_helper\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SUL Helper event subscriber.
 */
class SulHelperSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [];
  }

}
