<?php

namespace Drupal\sul_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Override original paragraph behavior plugin.
 */
class SuCardStylesBehaviors extends CardBehavior {

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['link_style']['#access'] = FALSE;
    return $element;
  }

}
