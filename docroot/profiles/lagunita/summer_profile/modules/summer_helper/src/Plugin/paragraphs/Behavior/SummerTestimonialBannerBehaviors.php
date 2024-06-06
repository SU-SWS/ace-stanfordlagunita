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
 * @codeCoverageIgnore
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
        'type_4' => 'Long quote (smaller font)',
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_testimonial_banner', 'sum_testimonial_banner_heading'),
    ];
    $element['sum_testimonial_banner_overlay_bkg'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Background Color'),
      '#empty_option' => $this->t('Poppy Light'),
      '#options' => [
        'olive' => $this->t('Olive Light'),
        'spirited' => $this->t('Spirited Light'),
        'spirited_dark' => $this->t('Spirited Dark'),
        'white' => $this->t('White'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('sum_testimonial_banner', 'sum_testimonial_banner_overlay_bkg'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Implements the view method.
  }

}
