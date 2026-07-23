<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * CSP profile helper hooks.
 */
class CspHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Adds a CSP-specific "Ultra slim" option to the "Space below section"
   * (bottom_margin) select on the Layout Paragraphs component form. The option
   * is defined upstream in stanford_layout_paragraphs, which is a shared module
   * we cannot edit; instead we extend the select here so the change stays scoped
   * to the CSP site. The layout plugin configuration form is built lazily via a
   * #process callback, so we attach our own #process callback to alter the
   * element once it exists.
   */
  #[Hook('form_layout_paragraphs_component_form_alter')]
  public function layoutParagraphsComponentFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (isset($form['layout_paragraphs']['#process'])) {
      $form['layout_paragraphs']['#process'][] = [static::class, 'addUltraSlimSpaceBelow'];
    }
  }

  /**
   * Process callback: add the "Ultra slim" (6px) space-below option.
   *
   * Static to keep the cached form serialization-safe.
   *
   * @param array $element
   *   The built layout_paragraphs behavior form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The altered element.
   */
  public static function addUltraSlimSpaceBelow(array $element, FormStateInterface $form_state): array {
    if (isset($element['config']['bottom_margin']['#options'])) {
      $element['config']['bottom_margin']['#options']['ultra-slim'] = t('Ultra slim');
      $element['config']['bottom_margin']['#description'] = t('This would be equivalent to "margin-bottom". For the "Ultra slim" option, be sure that the "Space below section" on the paragraph directly above this is set to "None". This space is equivalent to margin-top.');
    }
    return $element;
  }

}
