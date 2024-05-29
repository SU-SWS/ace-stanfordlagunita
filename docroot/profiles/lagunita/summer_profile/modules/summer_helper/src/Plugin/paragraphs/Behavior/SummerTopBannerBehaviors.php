<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Annotation\ParagraphsBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Manages and controlling the behavior and display of "top banner" element.
 *
 * @ParagraphsBehavior(
 *   id = "sum_top_banner_behavior",
 *   label = @Translation("Summer Top Banner Behavior"),
 *   description = @Translation("This plugin offers top banner variants."),
 *   weight = 1,
 * )
 */
class SummerTopBannerBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type): bool {
    return $paragraphs_type->id() == 'sum_top_banner_behavior';
  }

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
    $element['sum_top_banner_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Alignment'),
      '#empty_option' => $this->t('Right'),
      '#options' => [
        'left' => $this->t('Left'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_top_banner_behavior', 'sum_top_banner_alignment'),
    ];

    return $element;
  }

  /**
   * Included to view the top banner.
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // @todo Implement view() method.
  }

}
