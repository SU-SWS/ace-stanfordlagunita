<?php

declare(strict_types=1);

namespace Drupal\Tests\csp_helper\Unit\Hook;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\csp_helper\Hook\CspHelperHooks;
use Drupal\csp_helper\Layouts\CspOneColumn;
use Drupal\csp_helper\Layouts\CspThreeColumn;
use Drupal\csp_helper\Layouts\CspTwoColumn;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_patterns\Definition\PatternDefinition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the csp_helper hook implementations.
 */
#[CoversClass(CspHelperHooks::class)]
class CspHelperHooksTest extends UnitTestCase {

  private function makeContext(string $field_name): array {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getName')->willReturn($field_name);
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($definition);
    return ['items' => $items];
  }

  public function testColorFieldGetsStates(): void {
    $hooks = new CspHelperHooks();
    $element = [];
    $form_state = new FormState();
    $context = $this->makeContext('csp_card_bg_color');

    $hooks->fieldWidgetSingleElementFormAlter($element, $form_state, $context);

    $selector = '[name="behavior_plugins[su_card_styles][csp_card_variant]"]';
    $this->assertArrayHasKey('#states', $element);
    $this->assertSame(
      ['value' => 'poster'],
      $element['#states']['visible'][$selector]
    );
  }

  public function testOtherFieldUntouched(): void {
    $hooks = new CspHelperHooks();
    $element = [];
    $form_state = new FormState();
    $context = $this->makeContext('su_card_body');

    $hooks->fieldWidgetSingleElementFormAlter($element, $form_state, $context);

    $this->assertArrayNotHasKey('#states', $element);
  }

  /**
   * The card pattern gains a "poster" variant with its own modifier class.
   *
   * jumpstart_ui_preprocess() reads the modifier class out of the definition
   * as an array, so assert against toArray() rather than the object.
   */
  public function testPosterVariantIsRegistered(): void {
    $definitions = [
      'yaml:card' => new PatternDefinition([
        'id' => 'card',
        'variants' => ['postcard' => ['label' => 'Postcard', 'modifier_class' => 'su-card--horizontal']],
      ]),
      'yaml:stat_card' => new PatternDefinition(['id' => 'stat_card']),
    ];

    (new CspHelperHooks())->uiPatternsInfoAlter($definitions);

    $variants = $definitions['yaml:card']->toArray()['variants'];
    $this->assertSame('su-card--poster', $variants['poster']['modifier_class']);
    $this->assertSame('Poster', $variants['poster']['label']);
    // Upstream variants must survive.
    $this->assertSame('su-card--horizontal', $variants['postcard']['modifier_class']);
    // A pattern whose id merely contains "card" must be left alone.
    $this->assertArrayNotHasKey('poster', $definitions['yaml:stat_card']->toArray()['variants']);
  }

  /**
   * The hook is a no-op when the card pattern is not present.
   */
  public function testPosterVariantWithoutCardPattern(): void {
    $definitions = ['yaml:quote' => new PatternDefinition(['id' => 'quote'])];

    (new CspHelperHooks())->uiPatternsInfoAlter($definitions);

    $this->assertArrayNotHasKey('poster', $definitions['yaml:quote']->toArray()['variants']);
  }

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
