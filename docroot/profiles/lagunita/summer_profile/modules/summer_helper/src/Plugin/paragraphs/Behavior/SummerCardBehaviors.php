<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Add desired behaviors to paragraph.
 *
 * @codeCoverageIgnore No functionality to test.
 */
class SummerCardBehaviors extends CardBehavior {

  /**
   * Builds card behavior form for a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity object.
   * @param array $form
   *   An associative array representing the form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An updated associative array representing the form structure with the
   *   added 'card_variant' element.
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['sum_card_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Card variant'),
      '#default_option' => $this->t('Default'),
      '#options' => [
        'circle' => $this->t('Circle'),
        'semicircle-top' => $this->t('Semicircle top'),
        'semicircle-bottom' => $this->t('Semicircle bottom'),
        'oval' => $this->t('Oval'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'bg_color'),
    ];

    return $element;
  }
}
