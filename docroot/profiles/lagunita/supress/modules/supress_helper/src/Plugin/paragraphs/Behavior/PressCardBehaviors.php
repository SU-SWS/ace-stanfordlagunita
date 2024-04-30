<?php

namespace Drupal\supress_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Add desired behaviors to card plugin.
 *
 * @codeCoverageIgnore No functionality to test.
 */
class PressCardBehaviors extends CardBehavior {

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['card_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Card Style'),
      '#options' => [
        'bg-image' => $this->t('Background Image'),
      ],
      '#empty_option' => $this->t('Default'),
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'card_style'),
    ];
    $element['bg_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Color'),
      '#options' => [
        'magenta' => $this->t('Magenta'),
        'grass' => $this->t('Grass'),
        'steel' => $this->t('Steel'),
        'indigo' => $this->t('Indigo'),
      ],
      '#empty_option' => $this->t('None'),
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'bg_color'),
      '#states' => [
        'invisible' => [
          ['[name="behavior_plugins[su_card_styles][card_style]"]' => ['value' => '']],
        ],
      ],
    ];
    return $element;
  }

}
