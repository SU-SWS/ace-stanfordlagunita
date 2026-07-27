<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the csp_helper module.
 */
class CspHelperHooks {

  /**
   * Implements hook_theme().
   *
   * Registers the Faux Course Card preview templates as suggestions of the
   * base 'paragraph' hook.
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__csp_faux_course_card' => [
        'base hook' => 'paragraph',
        'template' => 'paragraph--csp-faux-course-card',
      ],
      'paragraph__csp_course_card_instructor' => [
        'base hook' => 'paragraph',
        'template' => 'paragraph--csp-course-card-instructor',
      ],
    ];
  }

  /**
   * Implements hook_paragraphs_behavior_info_alter().
   *
   * Swap the upstream card behavior for the CSP subclass so the Poster
   * variant option is available.
   */
  #[Hook('paragraphs_behavior_info_alter')]
  public function paragraphsBehaviorInfoAlter(array &$behaviors): void {
    if (isset($behaviors['su_card_styles'])) {
      $behaviors['su_card_styles']['class'] = 'Drupal\\csp_helper\\Plugin\\paragraphs\\Behavior\\CspCardBehaviors';
    }
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   *
   * Show the poster background color field only when the card's variant is
   * set to Poster.
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    if ($context['items']->getFieldDefinition()->getName() === 'csp_card_bg_color') {
      $selector = '[name="behavior_plugins[su_card_styles][csp_card_variant]"]';
      $element['#states']['visible'][$selector] = ['value' => 'poster'];
    }
  }

}
