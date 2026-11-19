<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Wraps loose text and inline HTML in paragraphs.
 *
 * Block-level elements (lists, tables, headings, existing paragraphs, etc.)
 * are kept as they are. Any text or inline markup between them is wrapped in
 * <p> tags. Two or more consecutive <br> tags start a new paragraph, and a
 * single <br> is kept as a line break. Literal "\n" sequences in the source are
 * treated as whitespace.
 *
 * Example:
 *
 * @code
 * process:
 *   body/value:
 *     plugin: csp_html_paragraphs
 *     source: description
 * @endcode
 */
#[MigrateProcess('csp_html_paragraphs')]
class HtmlParagraphs extends ProcessPluginBase {

  /**
   * Elements that must not be placed inside a paragraph.
   */
  const array BLOCK_TAGS = [
    'address',
    'article',
    'aside',
    'blockquote',
    'details',
    'div',
    'dl',
    'fieldset',
    'figure',
    'footer',
    'form',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'header',
    'hr',
    'nav',
    'ol',
    'p',
    'pre',
    'section',
    'table',
    'ul',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    if (!is_string($value)) {
      return $value;
    }

    $value = str_replace(['\r\n', '\n', '\r'], ' ', $value);
    if (trim($value) === '') {
      return '';
    }

    $dom = Html::load($value);
    $body = $dom->getElementsByTagName('body')->item(0);

    $output = [];
    $paragraph = '';
    $pending_breaks = 0;

    foreach (iterator_to_array($body->childNodes) as $node) {
      if ($node instanceof \DOMElement && in_array(strtolower($node->tagName), self::BLOCK_TAGS)) {
        $output[] = self::wrap($paragraph);
        $output[] = $dom->saveHTML($node);
        $paragraph = '';
        $pending_breaks = 0;
        continue;
      }

      if ($node instanceof \DOMElement && strtolower($node->tagName) === 'br') {
        $pending_breaks++;
        continue;
      }

      $html = $dom->saveHTML($node);
      // Whitespace between breaks shouldn't count as content.
      if ($node instanceof \DOMText && trim($html) === '') {
        $paragraph .= $pending_breaks ? '' : $html;
        continue;
      }

      if ($pending_breaks >= 2) {
        $output[] = self::wrap($paragraph);
        $paragraph = '';
      }
      elseif ($pending_breaks === 1 && trim($paragraph) !== '') {
        $paragraph = rtrim($paragraph) . '<br>';
      }
      $pending_breaks = 0;
      $paragraph .= $html;
    }
    $output[] = self::wrap($paragraph);

    return implode("\n", array_filter($output));
  }

  /**
   * Wraps a run of inline HTML in a paragraph, or returns an empty string.
   *
   * @param string $html
   *   Inline HTML.
   *
   * @return string
   *   The paragraph, or an empty string if there is no content.
   */
  protected static function wrap(string $html): string {
    $html = trim(preg_replace('/\s+/u', ' ', $html));
    return $html === '' ? '' : "<p>$html</p>";
  }

}
