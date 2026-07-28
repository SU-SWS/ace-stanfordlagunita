<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_layout_paragraphs\Layouts\OneColumn;

/**
 * One column layout with the CSP "Ultra slim" space-below option.
 */
class CspOneColumn extends OneColumn {

  use UltraSlimMarginTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->addUltraSlimMarginOption($form);
    return $form;
  }

}
