<?php

declare(strict_types=1);

namespace Drupal\Tests\csp_helper\Unit\Plugin\migrate\process;

use Drupal\csp_helper\Plugin\migrate\process\HtmlParagraphs;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the csp_html_paragraphs migrate process plugin.
 */
#[CoversClass(HtmlParagraphs::class)]
#[Group('csp_helper')]
class HtmlParagraphsTest extends UnitTestCase {

  /**
   * Transforms a value with the plugin.
   */
  protected function transform(mixed $value): mixed {
    $plugin = new HtmlParagraphs([], 'csp_html_paragraphs', []);
    return $plugin->transform($value, $this->createMock(MigrateExecutableInterface::class), new Row(), 'body/value');
  }

  /**
   * Tests transforming various strings.
   */
  #[DataProvider('providerTransform')]
  public function testTransform(mixed $value, mixed $expected): void {
    $this->assertSame($expected, $this->transform($value));
  }

  /**
   * Data provider for testTransform().
   */
  public static function providerTransform(): array {
    return [
      'non-string' => [NULL, NULL],
      'empty' => ['  ', ''],
      'plain text' => ['Plain text.', '<p>Plain text.</p>'],
      'inline html' => [
        'Some <strong>bold</strong> text.',
        '<p>Some <strong>bold</strong> text.</p>',
      ],
      'existing paragraph' => ['<p>Already</p>', '<p>Already</p>'],
      'single break' => ['Line one<br>Line two', '<p>Line one<br>Line two</p>'],
      'double break' => [
        'One<br><br>Two',
        "<p>One</p>\n<p>Two</p>",
      ],
      'trailing breaks' => ['Text<br><br>', '<p>Text</p>'],
      'text around a list' => [
        'Topics: \n<br><br>  \n<ul><li>One</li><li>Two</li></ul>\nOutro.',
        "<p>Topics:</p>\n<ul><li>One</li><li>Two</li></ul>\n<p>Outro.</p>",
      ],
      'utf-8' => ['Student’s “path”—yes', '<p>Student’s “path”—yes</p>'],
      'salesforce course description' => [
        'The course will cover the following topics: \n<br><br>  \n<ul><li>What metrics really matter when selecting a college</li><li>Escaping the high-pressure “rat race” of college admissions</li></ul>\nThis course is not designed to help students or parents with the college application process. It does, however, provide fresh insights.',
        "<p>The course will cover the following topics:</p>\n<ul><li>What metrics really matter when selecting a college</li><li>Escaping the high-pressure “rat race” of college admissions</li></ul>\n<p>This course is not designed to help students or parents with the college application process. It does, however, provide fresh insights.</p>",
      ],
    ];
  }

}
