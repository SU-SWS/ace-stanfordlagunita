<?php

declare(strict_types=1);

namespace Drupal\Tests\csp_helper\Unit\Hook;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\csp_helper\Hook\CspHelperHooks;
use Drupal\csp_helper\Layouts\CspOneColumn;
use Drupal\csp_helper\Layouts\CspThreeColumn;
use Drupal\csp_helper\Layouts\CspTwoColumn;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the csp_helper hook_layout_alter() class swapping.
 */
#[CoversClass(CspHelperHooks::class)]
class CspHelperHooksTest extends UnitTestCase {

  /**
   * hook_layout_alter() points the LP layouts at the CSP subclasses.
   */
  public function testLayoutAlterSwapsClasses(): void {
    $definitions = [
      'layout_paragraphs_1_column' => new LayoutDefinition(['id' => 'layout_paragraphs_1_column', 'class' => 'OriginalOne']),
      'layout_paragraphs_2_column' => new LayoutDefinition(['id' => 'layout_paragraphs_2_column', 'class' => 'OriginalTwo']),
      'layout_paragraphs_3_column' => new LayoutDefinition(['id' => 'layout_paragraphs_3_column', 'class' => 'OriginalThree']),
      // An unrelated layout must be left untouched.
      'layout_onecol' => new LayoutDefinition(['id' => 'layout_onecol', 'class' => 'Untouched']),
    ];

    (new CspHelperHooks())->layoutAlter($definitions);

    $this->assertSame(CspOneColumn::class, $definitions['layout_paragraphs_1_column']->getClass());
    $this->assertSame(CspTwoColumn::class, $definitions['layout_paragraphs_2_column']->getClass());
    $this->assertSame(CspThreeColumn::class, $definitions['layout_paragraphs_3_column']->getClass());
    $this->assertSame('Untouched', $definitions['layout_onecol']->getClass());
  }

  /**
   * The hook is a no-op when the targeted layouts are not present.
   */
  public function testLayoutAlterWithMissingLayouts(): void {
    $definitions = [
      'layout_onecol' => new LayoutDefinition(['id' => 'layout_onecol', 'class' => 'Untouched']),
    ];

    (new CspHelperHooks())->layoutAlter($definitions);

    $this->assertSame('Untouched', $definitions['layout_onecol']->getClass());
  }

}
