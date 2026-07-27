<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Layouts;

/**
 * Adds the CSP "Ultra slim" option to the "Space below section" select.
 *
 * The upstream stanford_layout_paragraphs layout plugins build a
 * `bottom_margin` select with only "Default"/"None". This trait is mixed into
 * the CSP layout subclasses so each can append the CSP-specific "Ultra slim"
 * (~6px) option after the parent form is built.
 */
trait UltraSlimMarginTrait {

  /**
   * Appends the "Ultra slim" option to the bottom_margin select.
   *
   * @param array $form
   *   The layout configuration form built by the parent layout plugin.
   */
  protected function addUltraSlimMarginOption(array &$form): void {
    if (isset($form['bottom_margin']['#options'])) {
      $form['bottom_margin']['#options']['ultra-slim'] = $this->t('Ultra slim');
      $form['bottom_margin']['#description'] = $this->t('This would be equivalent to "margin-bottom". For the "Ultra slim" option, be sure that the "Space below section" on the paragraph directly above this is set to "None".');
    }
  }

}
