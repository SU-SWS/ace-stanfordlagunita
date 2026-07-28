<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_layout_paragraphs\Layouts\ThreeColumn;

/**
 * Three column layout with the CSP "Ultra slim" and "Section width" options.
 */
class CspThreeColumn extends ThreeColumn {

  use UltraSlimMarginTrait;
  use SectionWidthTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['section_width' => 'full'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->addUltraSlimMarginOption($form);
    $this->addSectionWidthElement($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->submitSectionWidth($form_state);
  }

}
