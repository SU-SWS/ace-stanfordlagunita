<?php

namespace Drupal\csp_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Style options for the CSP Faux Course Card paragraph.
 */
#[ParagraphsBehavior(
  id: 'csp_faux_card_styles',
  label: new TranslatableMarkup('Faux Course Card Styles'),
  description: new TranslatableMarkup('Display options for the faux course card paragraph.')
)]
class CspFauxCardBehavior extends ParagraphsBehaviorBase {

  /**
   * Behavior setting key for the image position.
   */
  const IMAGE_POSITION = 'image_position';

  /**
   * Default image position.
   */
  const IMAGE_POSITION_DEFAULT = 'left';

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphs_type) {
    return $paragraphs_type->id() === 'csp_faux_course_card';
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form[self::IMAGE_POSITION] = [
      '#type' => 'select',
      '#title' => $this->t('Image position'),
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => self::getImagePosition($paragraph),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = [];
    if (self::getImagePosition($paragraph) === 'right') {
      $summary[] = [
        'label' => $this->t('Image position'),
        'value' => $this->t('Right'),
      ];
    }
    return $summary;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if (self::getImagePosition($paragraph) === 'right') {
      $build['#attributes']['class'][] = 'csp-faux-card-image-right';
    }
  }

  /**
   * Get the image position for a paragraph, defaulting to left.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Faux course card paragraph.
   *
   * @return string
   *   Either "left" or "right".
   */
  public static function getImagePosition(ParagraphInterface $paragraph): string {
    $position = $paragraph->getBehaviorSetting('csp_faux_card_styles', self::IMAGE_POSITION, self::IMAGE_POSITION_DEFAULT);
    return $position === 'right' ? 'right' : self::IMAGE_POSITION_DEFAULT;
  }

}
