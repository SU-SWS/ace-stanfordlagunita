<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Layouts;

use Drupal\Core\Form\FormStateInterface;

/**
 * Adds the CSP "Section width" select to multi-column layouts.
 *
 * This trait is mixed into the CSP 2- and 3-column layout subclasses only.
 * Single-column sections are intentionally excluded.
 */
trait SectionWidthTrait {

  /**
   * The available section width options.
   *
   * @return array
   *   Machine name keyed option labels. The px values match the front-end
   *   max-width mapping and are shown to help authors choose.
   */
  protected function sectionWidthOptions(): array {
    return [
      'full' => $this->t('Full (1500px)'),
      'wide' => $this->t('Wide (1300px)'),
      'standard' => $this->t('Standard (1140px)'),
    ];
  }

  /**
   * Appends the "Section width" select to the layout configuration form.
   *
   * @param array $form
   *   The layout configuration form built by the parent layout plugin.
   */
  protected function addSectionWidthElement(array &$form): void {
    $form['section_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Section width'),
      '#description' => $this->t('Constrains the maximum width of this multi-column section. Defaults to "Full", which leaves existing sections at their widest.'),
      '#options' => $this->sectionWidthOptions(),
      '#default_value' => $this->configuration['section_width'] ?? 'full',
    ];
  }

  /**
   * Persists the submitted "Section width" value into the layout config.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted layout configuration form state.
   */
  protected function submitSectionWidth(FormStateInterface $form_state): void {
    $this->configuration['section_width'] = $form_state->getValue('section_width') ?: 'full';
  }

}
