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

    // Switch to the "poster" ui_patterns variant registered by
    // CspHelperHooks::uiPatternsInfoAlter(), which adds the CSP-owned
    // "su-card--poster" modifier class that card-poster-preview.css targets.
    $build['#ds_configuration']['layout']['settings']['pattern']['variant'] = 'poster';

    if ($paragraph->hasField('csp_card_bg_color') && !$paragraph->get('csp_card_bg_color')->isEmpty()) {
      $hex = $paragraph->get('csp_card_bg_color')->first()->get('color')->getString();
      if ($hex) {
        $hex = ltrim($hex, '#');
        $style = $build['#attributes']['style'] ?? '';
        $build['#attributes']['style'] = $style . '--csp-poster-bg:#' . $hex . ';'
          . '--csp-poster-fg:' . self::contrastColor($hex) . ';';
      }
    }
  }

  /**
   * Picks a readable text color for a poster background.
   *
   * @param string $hex
   *   A background color as a six-digit hex string, without a leading "#".
   *
   * @return string
   *   Either '#fff' or '#2e2d29'.
   */
  private static function contrastColor(string $hex): string {
    $channels = [];
    foreach (sscanf($hex, '%2x%2x%2x') ?: [0, 0, 0] as $value) {
      $channel = $value / 255;
      $channels[] = $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
    // WCAG relative luminance. 0.179 is the crossover point at which white and
    // dark text give equal contrast against the background.
    $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    return $luminance > 0.179 ? '#2e2d29' : '#fff';
  }

}
