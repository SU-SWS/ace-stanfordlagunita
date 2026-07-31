<?php

namespace Drupal\Tests\csp_helper\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\csp_helper\Plugin\paragraphs\Behavior\CspCardBehaviors;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

  #[DataProvider('providerPosterBackgroundColors')]
  public function testPosterViewAddsVariantAndColorClass(string $hex): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->mockPosterParagraph($hex);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertContains('csp-card-variant-poster', $build['#attributes']['class']);
    $this->assertContains("csp-card-bg-$hex", $build['#attributes']['class']);
    $this->assertArrayNotHasKey('style', $build['#attributes']);
    $this->assertContains('csp_helper/card_poster_preview', $build['#attached']['library']);
  }

  /**
   * With no color picked the panel falls back to the default set in CSS.
   */
  public function testPosterViewWithoutColor(): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->mockPosterParagraph('');
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertSame(['csp-card-variant-poster'], $build['#attributes']['class']);
  }

  public function testNonPosterViewIsUntouched(): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn(NULL);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertArrayNotHasKey('#attributes', $build);
  }

  /**
   * The swatches offered by the color_field_widget_box widget.

   */
  public static function providerPosterBackgroundColors(): array {
    return [
      'cardinal red' => ['8c1515'],
      'lagunita blue' => ['007c92'],
      'plum' => ['620059'],
      'palo verde' => ['175e54'],
      'black' => ['2e2d29'],
      'fog light' => ['f4f4f4'],
    ];
  }

  /**
   * Builds a Poster-variant paragraph with the given background color.
   */
  private function mockPosterParagraph(string $hex): ParagraphInterface {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function ($module, $key) {
        return $key === 'csp_card_variant' ? 'poster' : NULL;
      });

    $list = $this->createMock(FieldItemListInterface::class);
    $list->method('getString')->willReturn($hex);
    $paragraph->method('get')->with('csp_card_bg_color')->willReturn($list);

    return $paragraph;
  }

}
