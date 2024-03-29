<?php

namespace Drupal\sul_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Teaser paragraph behaviors.
 *
 * @ParagraphsBehavior(
 *   id = "sul_teaser_styles",
 *   label = @Translation("SUL Teaser Styles"),
 *   description = @Translation("Style options for teaser paragraph")
 * )
 */
class TeaserBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $element = [];
    $element['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#description' => $this->t('Change the way the card looks. This will apply only when the area is large enough.'),
      '#empty_option' => $this->t('Normal'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_teaser_styles', 'orientation'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
      ],
    ];

    $element['background_sprinkles'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Sprinkles'),
      '#description' => $this->t('Choose the position of the "sprinkles". Used in conjunction with "Horizontal" orientation.'),
      '#empty_option' => $this->t('- Change the position -'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_teaser_styles', 'background_sprinkles'),
      '#options' => [
        'top_left' => $this->t('Top Left'),
        'top_right' => $this->t('Top Right'),
        'bottom_left' => $this->t('Bottom Left'),
        'bottom_right' => $this->t('Bottom Right'),
      ],
      '#states' => [
        'invisible' => [
          'select[name="behavior_plugins[sul_teaser_styles][orientation]"]' => ['value' => ''],
        ],
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Simple changes for the edit form.
    $build['#attributes']['class'][] = 'sul-orientation-' . $paragraph->getBehaviorSetting('sul_teaser_styles', 'orientation', 'vertical');
    $build['#attributes']['class'][] = 'sul-background-' . $paragraph->getBehaviorSetting('sul_teaser_styles', 'background', 'none');
    $build['#attributes']['class'][] = 'sul-sprinkles-' . $paragraph->getBehaviorSetting('sul_teaser_styles', 'background_sprinkles', 'top_left');
  }

}
