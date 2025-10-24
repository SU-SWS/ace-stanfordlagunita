<?php

namespace Drupal\sul_helper\Layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_layout_paragraphs\Layouts\ThreeColumn;

/**
 * Three column layout class
 */
class SulThreeColumn extends ThreeColumn  {
  use LayoutWithHeading;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->addHeadingElement($form, $form_state);
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->submitHeadingForm($form, $form_state);
  }
}