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
  public function testPosterViewAddsClassAndColor(string $hex, string $expected_fg): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->mockPosterParagraph($hex);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertContains('csp-card-variant-poster', $build['#attributes']['class']);
    $this->assertStringContainsString("--csp-poster-bg:#$hex", $build['#attributes']['style']);
    $this->assertStringContainsString("--csp-poster-fg:$expected_fg", $build['#attributes']['style']);
    $this->assertContains('csp_helper/card_poster_preview', $build['#attached']['library']);
  }

  public function testPosterViewSwitchesToHorizontalPattern(): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->mockPosterParagraph('8c1515');
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    // "postcard" carries Decanter's su-card--horizontal modifier class, which
    // is what lays the card out side by side.
    $this->assertSame(
      'postcard',
      $build['#ds_configuration']['layout']['settings']['pattern']['variant']
    );
  }

  public function testNonPosterViewIsUntouched(): void {
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CspCardBehaviors([], '', [], $field_manager);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn(NULL);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertArrayNotHasKey('#ds_configuration', $build);
    $this->assertArrayNotHasKey('#attributes', $build);
  }

  /**
   * The six colors offered by the color_field_widget_box widget.
   *
   * Mirrors the "default_colors" setting on the csp_card_bg_color widget in
   * config_split.patch.core.entity_form_display.paragraph.stanford_card.default.
   */
  public static function providerPosterBackgroundColors(): array {
    return [
      'cardinal red' => ['8c1515', '#fff'],
      'lagunita blue' => ['007c92', '#fff'],
      'plum' => ['620059', '#fff'],
      'palo verde' => ['175e54', '#fff'],
      'black' => ['2e2d29', '#fff'],
      // The one light swatch, which needs dark text to stay readable.
      'fog light' => ['f4f4f4', '#2e2d29'],
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

    $color_prop = $this->createMock(TypedDataInterface::class);
    $color_prop->method('getString')->willReturn($hex);
    $item = $this->createMock(ColorFieldType::class);
    $item->method('get')->with('color')->willReturn($color_prop);
    $list = $this->createMock(FieldItemListInterface::class);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('first')->willReturn($item);
    $paragraph->method('hasField')->with('csp_card_bg_color')->willReturn(TRUE);
    $paragraph->method('get')->with('csp_card_bg_color')->willReturn($list);

    return $paragraph;
  }

}
