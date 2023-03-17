<?php

namespace Drupal\sul_helper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Simple Button paragraph behaviors.
 *
 * @ParagraphsBehavior(
 *   id = "sul_button_styles",
 *   label = @Translation("SUL Simple Button Styles"),
 *   description = @Translation("Style options for Button paragraph")
 * )
 */
class SimpleButtonBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'sul_button';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $element = [];

    $element['background'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Color'),
      '#description' => $this->t('Change the background color behind the text.'),
      '#empty_option' => $this->t('Black'),
      '#default_value' => $paragraph->getBehaviorSetting('sul_button_styles', 'background'),
      '#options' => [
        'gray' => $this->t('Gray'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Simple changes for the edit form.
  }
}
