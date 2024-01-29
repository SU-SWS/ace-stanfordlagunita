<?php

namespace Drupal\sul_helper\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'Clean Html' filter.
 *
 * @Filter(
 *   id = "sul_clean_html",
 *   title = @Translation("Clean Html"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 99
 * )
 */
class SulCleanHtml extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Remove line breaks
    $text = preg_replace( '/(\r\n)+|\r+|\n+|\t+/', ' ', $text );
    // Remove html comments.
    $text = preg_replace('/<!--.*?>/', '', $text);
    // Remove white space between tags.
    $text = preg_replace('/> +?</', '><', $text);
    return new FilterProcessResult(trim($text));
  }

}
