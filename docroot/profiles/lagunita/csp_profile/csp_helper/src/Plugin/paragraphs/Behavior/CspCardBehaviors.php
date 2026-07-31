<?php

namespace Drupal\csp_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;

/**
 * Adds CSP-specific style options (Poster variant) to the card behavior.
 */
class CspCardBehaviors extends CardBehavior {

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['csp_card_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Card variant'),
      '#empty_option' => $this->t('Default'),
      '#options' => [
        'poster' => $this->t('Poster'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'csp_card_variant'),
    ];
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    parent::view($build, $paragraph, $display, $view_mode);

    if ($paragraph->getBehaviorSetting('su_card_styles', 'csp_card_variant') !== 'poster') {
      return;
    }

    $build['#attributes']['class'][] = 'csp-card-variant-poster';
    $build['#attached']['library'][] = 'csp_helper/card_poster_preview';
    
    if ($bg_color = $paragraph->get('csp_card_bg_color')?->getString()) {
      $build['#attributes']['class'][] = "csp-card-bg-$bg_color";
    }
  }

}
