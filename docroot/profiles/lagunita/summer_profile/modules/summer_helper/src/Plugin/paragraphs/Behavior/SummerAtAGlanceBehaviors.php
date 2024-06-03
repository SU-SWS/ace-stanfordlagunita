<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Manages and controlling the behavior and display of "facts" element.
 *
 * @ParagraphsBehavior(
 *   id = "sum_at_a_glance_behavior",
 *   label = @Translation("Summer Top Banner Behavior"),
 *   description = @Translation("This plugin offers top banner variants."),
 *   weight = 0,
 * )
 */
class SummerAtAGlanceBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'sum_at_a_glance';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['sum_at_a_glance_alignment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overlay Alignment'),
      '#description' => $this->t('Aligns overlay to the left.'),
      '#default_value' => $paragraph->getBehaviorSetting('sum_at_a_glance', 'sum_at_a_glance_alignment'),
    ];
  }

  /**
   * Included to view the top banner.
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Simple changes for the edit form.
  }

}
