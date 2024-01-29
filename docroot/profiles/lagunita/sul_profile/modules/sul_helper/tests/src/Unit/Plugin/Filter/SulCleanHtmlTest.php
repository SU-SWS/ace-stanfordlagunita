<?php

namespace Drupal\Tests\sul_helper\Unit\Plugin\Filter;

use Drupal\sul_helper\Plugin\Filter\SulCleanHtml;
use Drupal\Tests\UnitTestCase;

/**
 * Class SulCleanHtmlTest.
 */
class SulCleanHtmlTest extends UnitTestCase {

  /**
   * Test the clean html filter.
   */
  public function testFilter() {
    $config = [];
    $definition = ['provider' => 'sul_helper'];
    $filter = new SulCleanHtml($config, '', $definition);
    $result = $filter->process("\r\n<!-- FOO BAR BAZ-->\n\n<div>foo</div>\n\n\n<div>\r\nbar\r\n\r\nbaz</div>\r\n", NULL);

    $this->assertEquals('<div>foo</div><div> bar baz</div>', (string) $result);
  }

}
