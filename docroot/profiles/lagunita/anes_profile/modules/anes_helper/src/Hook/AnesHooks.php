<?php

namespace Drupal\anes_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Handles hooks for Anesthesiology.
 */
class AnesHooks {

  /**
   * Add custom paragraphs behavior.
   */
  #[Hook('paragraphs_behavior_info_alter')]
  public function onParagraphsBehaviorInfoAlter(array &$paragraphs_behavior): void {
    // Add custom card styles behaviors.
    $paragraphs_behavior['su_card_styles']['class'] = '\Drupal\anes_helper\Plugin\paragraphs\Behavior\AnesCardBehaviors';
  }

}
