<?php

namespace Drupal\Tests\csp_helper\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType;
use Drupal\csp_helper\Plugin\paragraphs\Behavior\CspCardBehaviors;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\csp_helper\Plugin\paragraphs\Behavior\CspCardBehaviors
 * @group csp_helper
 */
class CspCardBehaviorsTest extends UnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  public function testAppliesToCard(): void {
    $type = $this->createMock(ParagraphsType::class);
    $type->method('id')->willReturn('stanford_card');
    $this->assertTrue(CspCardBehaviors::isApplicable($type));
  }

  public function testVariantSelectAdded(): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn(NULL);

    $form = [];
    $form_state = new FormState();
    $element = $behavior->buildBehaviorForm($paragraph, $form, $form_state);

    $this->assertArrayHasKey('csp_card_variant', $element);
    $this->assertSame('select', $element['csp_card_variant']['#type']);
    $this->assertArrayHasKey('poster', $element['csp_card_variant']['#options']);
    // Inherited base element still present.
    $this->assertArrayHasKey('heading', $element);
  }

  public function testPosterViewAddsClassAndColor(): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function ($module, $key) {
        return $key === 'csp_card_variant' ? 'poster' : NULL;
      });

    // Mock the color field: get('csp_card_bg_color')->first()->get('color')->getString() === '#620059'
    $color_prop = $this->createMock(TypedDataInterface::class);
    $color_prop->method('getString')->willReturn('#620059');
    $item = $this->createMock(ColorFieldType::class);
    $item->method('get')->with('color')->willReturn($color_prop);
    $list = $this->createMock(FieldItemListInterface::class);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('first')->willReturn($item);
    $paragraph->method('hasField')->with('csp_card_bg_color')->willReturn(TRUE);
    $paragraph->method('get')->with('csp_card_bg_color')->willReturn($list);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertContains('csp-card-variant-poster', $build['#attributes']['class']);
    $this->assertStringContainsString('--csp-poster-bg:#620059', $build['#attributes']['style']);
    $this->assertContains('csp_helper/card_poster_preview', $build['#attached']['library']);
  }

}
