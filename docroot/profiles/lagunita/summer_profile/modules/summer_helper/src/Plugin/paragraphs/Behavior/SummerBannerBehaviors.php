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
 * @codeCoverageIgnore
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
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_banner';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
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
