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
 * Manages and controlling the behavior and display of banner component.
 *
 * @ParagraphsBehavior(
 *   id = "sum_banner_behaviors",
 *   label = @Translation("Summer Banner Component Behavior"),
 *   description = @Translation("This plugin offers banner variants."),
 *   weight = 0,
 * )
 */
class SummerBannerBehaviors extends ParagraphsBehaviorBase {

  /**
   * Limits to Stanford banner.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type
   *   The ParagraphsType entity from the Paragraphs module to be evaluated.
   *
   * @return bool
   *   Returns TRUE if the limit is applicable, FALSE otherwise.
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_banner';
  }

  /**
   * Builds the background color, alignment and button for variant.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph object which provides context for the variant.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form which includes user input data.
   *
   * @return array
   *   Array of the background color, alignment and button data for the variant.
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['sum_banner_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Alignment'),
      '#empty_option' => $this->t('Right'),
      '#options' => [
        'left' => $this->t('Left'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_banner_behaviors', 'sum_banner_alignment'),
    ];
    $element['sum_banner_overlay_bkg'] = [
      '#type' => 'select',
      '#title' => $this->t('Banner Background Color'),
      '#empty_option' => $this->t('Poppy Light'),
      '#options' => [
        'olive' => $this->t('Olive Light'),
        'spirited' => $this->t('Spirited Light'),
        'spirited_dark' => $this->t('Spirited Dark'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_banner_behaviors', 'sum_banner_overlay_bkg'),
    ];
    $element['sum_banner_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Semi-circle Button'),
      '#default_value' => $paragraph->getBehaviorSetting('sum_banner_behaviors', 'sum_banner_button'),
    ];

    return $element;
  }

  /**
   * Included to view the top banner.
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Simple changes for the edit form.
  }

}
