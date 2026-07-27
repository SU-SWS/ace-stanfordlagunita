<?php

namespace Drupal\Tests\csp_helper\Unit\Hook;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\csp_helper\Hook\CspHelperHooks;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\csp_helper\Hook\CspHelperHooks
 * @group csp_helper
 */
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

}
