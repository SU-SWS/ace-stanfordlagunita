<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Style options for the Faux Course Card paragraph.
 *
 * Only exposes the heading level, matching the affordance the other card
 * paragraphs offer through their own behavior plugins.
 */
#[ParagraphsBehavior(
  id: 'csp_course_card_styles',
  label: new TranslatableMarkup('Faux Course Card Styles'),
  description: new TranslatableMarkup('Style options for the Faux Course Card paragraph.')
)]
class CspFauxCourseCardBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type): bool {
    return $paragraphs_type->id() === 'csp_faux_course_card';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['heading' => 'h2'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['heading'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading Level'),
      '#description' => $this->t('The card title is a heading. Choose the level that sits one step below the heading above this card, so the page outline stays in order.'),
      '#options' => [
        'h2' => 'H2',
        'h3' => 'H3',
      ],
      '#default_value' => $paragraph->getBehaviorSetting('csp_course_card_styles', 'heading', 'h2'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * ParagraphsBehaviorBase leaves this interface method unimplemented, so a
   * concrete plugin must declare it. Nothing to alter at render time: the
   * paragraph template reads the setting off the paragraph itself.
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode): void {}

}
