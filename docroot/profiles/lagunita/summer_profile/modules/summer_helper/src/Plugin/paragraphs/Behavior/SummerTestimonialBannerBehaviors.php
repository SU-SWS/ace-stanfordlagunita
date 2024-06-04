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
 *   id = "sum_testimonial_banner",
 *   label = @Translation("Summer Testimonial Banner Behavior"),
 *   description = @Translation("This plugin offers top banner variants."),
 *   weight = 1,
 * )
 */
class SummerTestimonialBannerBehaviors extends ParagraphsBehaviorBase {
  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type): bool {
    return $paragraphs_type->id() == 'sum_testimonial';
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['sum_testimonial_banner_align'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('Aligns overlay to the left.'),
      '#title' => $this->t('Align left'),
      '#default_value' => $paragraph->getBehaviorSetting('sum_testimonial_banner', 'sum_testimonal_banner_align'),
    ];
    $element['sum_testimonial_banner_heading'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading level'),
      '#description' => $this->t('Options for smaller font size for larger quotes.'),
      '#empty_option' => $this->t('None'),
      '#options' => [
        'Long quote' => 'type_4',
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_testimonial_banner', 'sum_testimonial_banner_heading', 'h2'),
    ];

    return $element;
  }

  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Implements the view method.
  }

}
