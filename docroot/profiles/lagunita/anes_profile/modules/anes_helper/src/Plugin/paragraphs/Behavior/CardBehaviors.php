<?php

namespace Drupal\anes_helper\Plugin\paragraphs\Behavior;

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
 *   id = "anes_card_styles",
 *   label = @Translation("Anesthesiology Card Styles"),
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
    // $element['hide_rosette'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Hide Rosette'),
    //   '#default_value' => $paragraph->getBehaviorSetting('sul_card_styles', 'hide_rosette', FALSE),
    //   '#description' => $this->t('Check this box to hide the rosette for the horizontal card.'),
    // ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // ParagraphsBehaviorBase requires this method.
  }
}
