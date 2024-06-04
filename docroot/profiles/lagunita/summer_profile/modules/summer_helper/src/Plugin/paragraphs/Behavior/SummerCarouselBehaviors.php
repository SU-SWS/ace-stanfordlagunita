<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Manages and controlling the behavior and display of "Carousel" element.
 *
 * @codeCoverageIgnore
 *
 * @ParagraphsBehavior(
 *   id = "sum_carousel",
 *   label = @Translation("Summer Carousel Behavior"),
 *   description = @Translation("This plugin offers carousel variants.")
 * )
 */
class SummerCarouselBehaviors extends ParagraphsBehaviorBase {

  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'sum_carousel';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['sum_carousel_arc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show an arc on top'),
      '#default_value' => $paragraph->getBehaviorSetting('sum_carousel', 'sum_carousel_arc'),
    ];
    $element['sum_carousel_text_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Top Text Size'),
      '#empty_option' => $this->t('Large'),
      '#options' => [
        'small' => $this->t('Small'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_carousel', 'sum_carousel_text_size'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Nothing to do.
  }

}
