<?php

namespace Drupal\anes_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Card paragraph behaviors.
 *
 * @ParagraphsBehavior(
 *   id = "anes_card_styles",
 *   label = @Translation("Anesthesiology Card Styles"),
 *   description = @Translation("Style options for card paragraph")
 * )
 */
class AnesCardBehaviors extends CardBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['show_bottom_flourish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Bottom Flourish'),
      '#default_value' => $paragraph->getBehaviorSetting('anes_card_styles', 'show_bottom_flourish', FALSE),
      '#description' => $this->t('Add a decorative visual element to the bottom of the card.'),
    ];
    return $element;
  }
}
