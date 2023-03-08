<?php

namespace Drupal\sul_helper\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\field_event_dispatcher\Event\Field\WidgetCompleteFormAlterEvent;
use Drupal\field_event_dispatcher\FieldHookEvents;
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
      FieldHookEvents::WIDGET_COMPLETE_FORM_ALTER => ['onWidgetFormAlter'],
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

  /**
   * Widget form alter event.
   *
   * @param \Drupal\field_event_dispatcher\Event\Field\WidgetCompleteFormAlterEvent $event
   *   Triggered event.
   */
  public function onWidgetFormAlter(WidgetCompleteFormAlterEvent $event): void {
    $context = $event->getContext();
    if ($context['items']->getName() == 'sul_contact__branch') {
      $widget_form = &$event->getWidgetCompleteForm();
      $widget_form['widget']['#element_validate'][] = [
        self::class,
        'validateBranchField',
      ];
    }
  }

  /**
   * Validate the branch field for contact card paragraphs.
   *
   * @param array $element
   *   Field widget form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete entity form.
   */
  public static function validateBranchField(array $element, FormStateInterface $form_state, array $complete_form): void {
    /** @var \Drupal\layout_paragraphs\Form\EditComponentForm $object */
    $object = $form_state->getBuildInfo()['callback_object'];
    $value = $form_state->getValue($element['#parents']);
    $parent_entity = $object->getParagraph()->getParentEntity();
    foreach ($value as $target) {
      if ($parent_entity && $target['target_id'] == $parent_entity->id()) {
        $form_state->setError($element, 'This would create circular reference. Please create custom contact information.');
      }
    }
  }

}
