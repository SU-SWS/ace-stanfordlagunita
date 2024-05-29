<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Manages and controlling the behavior and display of "top banner" element.
 *
 * @ParagraphsBehavior(
 *   id = "sum_banner_behavior",
 *   label = @Translation("Summer Banner Component Behavior"),
 *   description = @Translation("This plugin offers banner variants."),
 *   weight = 0,
 * )
 */
class SummerBannerBehaviors extends ParagraphsBehaviorBase {

  public static function isApplicable(ParagraphsType $paragraphs_type): bool {
    return $paragraphs_type->id() == 'sum_banner_behavior';
  }

  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['sum_banner_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Alignment'),
      '#empty_option' => $this->t('Right'),
      '#options' => [
        'left' => $this->t('Left'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_banner_behavior', 'sum_banner_alignment'),
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
      '#default_value' => $paragraph->getBehaviorSetting('sum_banner_behavior', 'sum_banner_overlay_bkg'),
    ];
    $element['sum_banner_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Semi-circle Button'),
      '#default_value' => $paragraph->getBehaviorSetting('sum_top_banner_behavior', 'sum_top_banner_arc'),
    ];
  }

  /**
   * Included to view the banner variant.
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // @todo Implement view() method.
  }

}
