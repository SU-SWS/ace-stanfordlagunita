<?php

namespace Drupal\sul_helper\Layouts;

use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;

/**
 * Two column layout class
 */
class SulTwoColumn extends MultiWidthLayoutBase {

  /**
   * {@inheritDoc}
   */
  protected function getWidthOptions() {
    return [
      '50-50' => '50% - 50%',
      '33-67' => '33% - 67%',
      '67-33' => '67% - 33%',
    ];
  }

}
