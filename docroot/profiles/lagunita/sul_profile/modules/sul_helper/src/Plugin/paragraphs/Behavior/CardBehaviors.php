<?php

namespace Drupal\sul_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Card paragraph behaviors.
 *
 * @ParagraphsBehavior(
 *   id = "sul_card_styles",
 *   label = @Translation("SUL Card Styles"),
 *   description = @Translation("Style options for card paragraph")
 * )
 */
class CardBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_card';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $element = [];

    $element['link_display_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Card Link Style'),
      '#description' => $this->t('Change the appearance of the link on a card.'),
      '#empty_option' => $this->t('Primary Button'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_card_styles', 'link_display_style'),
      '#options' => [
        'secondary_button' => $this->t('Secondary Button'),
        'cta_button' => $this->t('CTA'),
      ],
    ];

    $element['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Variant'),
      '#description' => $this->t('Select how this card should be displayed. Normal cards use the standard vertical layout, while Feature cards display horizontally in single-column layouts and vertically in multi-column layouts.'),
      '#empty_option' => $this->t('Normal'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_card_styles', 'orientation'),
      '#options' => [
        'horizontal' => $this->t('Feature'),
      ],
    ];

    $element['background_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Card Color'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_card_styles', 'background_color', 'fog_light'),
      '#options' => [
        'fog_light' => $this->t('Fog Light'),
        'cardinal_red' => $this->t('Cardinal Red'),
      ],
    ];
    
    $element['hide_rosette'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Rosette'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_card_styles', 'hide_rosette', FALSE),
      '#description' => $this->t('Check this box to hide the rosette for the horizontal card.'),
    ];
    

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Simple changes for the edit form.
    $build['#attributes']['class'][] = 'sul-orientation-' . $paragraph->getBehaviorSetting('sul_card_styles', 'orientation', 'vertical');
  }
}
