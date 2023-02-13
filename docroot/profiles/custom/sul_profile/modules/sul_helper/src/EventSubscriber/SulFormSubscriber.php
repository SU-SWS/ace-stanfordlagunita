<?php

namespace Drupal\sul_helper\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SUL Helper form event subscriber.
 */
class SulFormSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'hook_event_dispatcher.form_layout_paragraphs_component_form.alter' => ['layoutParagraphComponentFormAlter'],
    ];
  }

  /**
   * Modify the layout paragraph component form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered Event.
   */
  public function layoutParagraphComponentFormAlter(FormIdAlterEvent $event): void {
    $form = &$event->getForm();
    $paragraph = $event->getFormState()->getFormObject()->getParagraph();
    if ($paragraph->bundle() == 'sul_contact_card') {
      $state = [
        'visible' => ['[name="sul_contact__branch"]' => ['value' => '_none']],
      ];
      $form['sul_contact__email']['#states'] = $state;
      $form['sul_contact__hours']['#states'] = $state;
      $form['sul_contact__image']['#states'] = $state;
      $form['sul_contact__link']['#states'] = $state;
      $form['sul_contact__phone']['#states'] = $state;
      $form['sul_contact__title']['#states'] = $state;

    }
  }

}
