<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Add desired behaviors to paragraph.
 *
 * @codeCoverageIgnore
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
      '#empty_option' => $this->t('Default'),
      '#options' => [
        'pill' => $this->t('Pill'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'sum_card_variant'),
    ];
    $element['sum_card_bg_color_variant'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No Card Background'),
      'description' => $this->t('Removes background from card.'),
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'sum_card_bg_color_variant'),
      'weight' => 2,
      '#states' => [
        'invisible' => [
          '[name="behavior_plugins[su_card_styles][sum_card_variant]"]' => ['value' => 'pill'],
        ],
      ],
    ];
    $element['sum_card_pill_bg_color_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Pill Card Background Color'),
      '#empty_option' => $this->t('Poppy Light'),
      '#options' => [
        'semitransparent_poppy' => $this->t('Semitransparent Poppy Light'),
        'olive' => $this->t('Olive Light'),
        'semitransparent_olive' => $this->t('Semitransparent Olive Light'),
        'spirited' => $this->t('Spirited Light'),
        'semitransparent_spirited' => $this->t('Semitransparent Spirited Light'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'sum_card_pill_bg_color_variant'),
      '#states' => [
        'visible' => [
          '[name="behavior_plugins[su_card_styles][sum_card_variant]"]' => ['value' => 'pill'],
        ],
      ],
    ];

    return $element;
  }

}
