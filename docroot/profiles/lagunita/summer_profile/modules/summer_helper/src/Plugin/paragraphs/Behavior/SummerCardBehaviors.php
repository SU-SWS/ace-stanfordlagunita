<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

// Your class definition
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Add desired behaviors to card plugin.
 *
 * @codeCoverageIgnore No functionality to test.
 */
class SummerCardBehaviors extends CardBehavior {

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['card_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Card variant'),
      '#options' => [
        'circle' => $this->t('Circle'),
        'semicircle-top' => $this->t('Semicircle top'),
        'semicircle-bottom' => $this->t('Semicircle bottom'),
        'oval' => $this->t('Oval'),
      ]
    ];
    return $element;
  }
}
