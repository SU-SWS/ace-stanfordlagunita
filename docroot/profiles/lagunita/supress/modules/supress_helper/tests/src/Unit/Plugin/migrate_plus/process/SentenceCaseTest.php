<?php
namespace Drupal\supress_helper\Tests\Plugin\migrate_plus\process;

use Drupal\Tests\UnitTestCase;
use Drupal\supress_helper\Plugin\migrate_plus\process\SentenceCase;

/**
 * Tests the SentenceCase process plugin.
 *
 * @group supress_helper
 */
class SentenceCaseTest extends UnitTestCase {

  /**
   * Tests the transform method.
   */
  public function testTransform() {
    $plugin = new SentenceCase([], 'sentence_case', []);

    // Test with a lowercase sentence.
    $value = 'this is a test sentence.';
    $expected = 'This is a test sentence.';
    $this->assertEquals($expected, $plugin->transform($value));

    // Test with an uppercase sentence.
    $value = 'THIS IS ANOTHER TEST SENTENCE.';
    $expected = 'This is another test sentence.';
    $this->assertEquals($expected, $plugin->transform($value));

    // Test with a mixed case sentence.
    $value = 'ThIs Is A MiXeD CaSe SeNtEnCe.';
    $expected = 'This is a mixed case sentence.';
    $this->assertEquals($expected, $plugin->transform($value));
  }

}
