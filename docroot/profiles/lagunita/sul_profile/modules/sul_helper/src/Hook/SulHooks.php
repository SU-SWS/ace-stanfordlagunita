<?php

namespace Drupal\sul_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * SUL Helper form event subscriber.
 */
class SulHooks {

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user account.
   */
  public function __construct(protected AccountProxyInterface $currentUser) {}

  /**
   * Modify the layout paragraph component form.
   */
  #[Hook('form_layout_paragraphs_component_form_alter')]
  public function layoutParagraphComponentFormAlter(array &$form, FormStateInterface $formState): void {
    $paragraph = $formState->getFormObject()->getParagraph();
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
      $form['sul_contact__address']['#states'] = $state;
      $form['sul_contact__map_link']['#states'] = $state;
    }
  }

  /**
   * Widget form alter event.
   */
  #[Hook('field_widget_complete_form_alter')]
  public function onWidgetFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    if ($context['items']->getName() == 'sul_contact__branch') {
      $field_widget_complete_form['widget']['#element_validate'][] = [
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

  /**
   * Implements hook_viewfield_argument_suggestion_vocabs_alter().)
   */
  #[Hook('viewfield_argument_suggestion_vocabs_alter')]
  public function viewfieldArgVocabs(array &$vocabs, array $view) {
    if ($view['view'] == 'sul_people') {
      $vocabs[] = 'stanford_person_types';
    }
    if ($view['view'] == 'sul_events') {
      $vocabs[] = 'stanford_event_types';
      $vocabs[] = 'event_audience';
    }
  }

}
