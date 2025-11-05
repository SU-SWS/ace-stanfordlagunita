<?php

namespace Drupal\sul_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Featured Collections paragraph behaviors.
 *
 * @ParagraphsBehavior(
 *   id = "sul_feat_collections_styles",
 *   label = @Translation("SUL Featured Collections Styles"),
 *   description = @Translation("Style options for Featured Collections paragraph")
 * )
 */
class FeaturedCollectionsBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'sul_feat_collection';
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
      '#default_value' => $paragraph->getBehaviorSetting('sul_feat_collections_styles', 'link_display_style'),
      '#options' => [
        'secondary_button' => $this->t('Secondary Button'),
        'cta_button' => $this->t('CTA'),
      ],
    ];

     // Disable Wave background
    $element['disable_wave_background'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Wave Accent Background'),
      '#description' => $this->t('Remove the wave accent background behind the featured collection cards.'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_feat_collections_styles', 'disable_wave_background', FALSE),
    ];

     // Add the heading gradient accent field
    $element['display_heading_gradient'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Heading Gradient Accent'),
      '#description' => $this->t('Add a gradient accent line to the right of list heading.'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_feat_collections_styles', 'display_heading_gradient', FALSE),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Simple changes for the edit form.
  }

}
