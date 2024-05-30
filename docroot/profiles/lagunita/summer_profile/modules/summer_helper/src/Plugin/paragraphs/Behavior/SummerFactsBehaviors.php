<?php

namespace Drupal\summer_helper\Plugin\paragraphs\Behavior;

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphsBehaviorBase;

class SummerTopBannerBehaviors extends ParagraphsBehaviorBase {
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_card';
  }

  
}

