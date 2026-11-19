<?php

namespace Drupal\Tests\csp_helper\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\csp_helper\Plugin\paragraphs\Behavior\CspFauxCardBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the faux course card image position behavior.
 */
#[CoversClass(CspFauxCardBehavior::class)]
#[Group('csp_helper')]
class CspFauxCardBehaviorTest extends UnitTestCase {

  /**
   * Behavior plugin under test.
   *
   * @var \Drupal\csp_helper\Plugin\paragraphs\Behavior\CspFauxCardBehavior
   */
  protected $behavior;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $this->behavior = new CspFauxCardBehavior([], 'csp_faux_card_styles', [], $field_manager);
  }

  /**
   * The behavior only applies to the faux course card paragraph type.
   */
  #[DataProvider('providerApplicable')]
  public function testIsApplicable(string $type_id, bool $expected): void {
    $type = $this->createMock(ParagraphsTypeInterface::class);
    $type->method('id')->willReturn($type_id);
    $this->assertSame($expected, CspFauxCardBehavior::isApplicable($type));
  }

  /**
   * Data provider for testIsApplicable().
   */
  public static function providerApplicable(): array {
    return [
      'faux course card' => ['csp_faux_course_card', TRUE],
      'card' => ['stanford_card', FALSE],
      'similar prefix' => ['csp_faux_course_card_2', FALSE],
      'empty' => ['', FALSE],
    ];
  }

  /**
   * Form contains the image position select with left as the default.
   */
  public function testFormDefaultsToLeft(): void {
    $paragraph = $this->mockParagraph(NULL);
    $form = [];
    $element = $this->behavior->buildBehaviorForm($paragraph, $form, new FormState());

    $this->assertArrayHasKey('image_position', $element);
    $select = $element['image_position'];
    $this->assertSame('select', $select['#type']);
    $this->assertSame('Image position', (string) $select['#title']);
    $this->assertSame(['left', 'right'], array_keys($select['#options']));
    $this->assertSame('Left', (string) $select['#options']['left']);
    $this->assertSame('Right', (string) $select['#options']['right']);
    $this->assertArrayNotHasKey('#empty_option', $select);
    $this->assertSame('left', $select['#default_value']);
  }

  /**
   * A saved "right" setting becomes the form default.
   */
  public function testFormDefaultsToSavedRight(): void {
    $paragraph = $this->mockParagraph('right');
    $form = [];
    $element = $this->behavior->buildBehaviorForm($paragraph, $form, new FormState());
    $this->assertSame('right', $element['image_position']['#default_value']);
  }

  /**
   * Stored values are normalized to "left" or "right".
   */
  #[DataProvider('providerImagePosition')]
  public function testGetImagePosition(mixed $stored, string $expected): void {
    $paragraph = $this->mockParagraph($stored);
    $this->assertSame($expected, CspFauxCardBehavior::getImagePosition($paragraph));
  }

  /**
   * Data provider for testGetImagePosition().
   */
  public static function providerImagePosition(): array {
    return [
      'missing' => [NULL, 'left'],
      'left' => ['left', 'left'],
      'right' => ['right', 'right'],
      'empty string' => ['', 'left'],
      'garbage' => ['center', 'left'],
      'wrong case' => ['Right', 'left'],
      'array' => [['right'], 'left'],
      'integer' => [1, 'left'],
    ];
  }

  /**
   * Settings summary is only shown for the non-default position.
   */
  public function testSettingsSummary(): void {
    $this->assertSame([], $this->behavior->settingsSummary($this->mockParagraph(NULL)));
    $this->assertSame([], $this->behavior->settingsSummary($this->mockParagraph('left')));

    $summary = $this->behavior->settingsSummary($this->mockParagraph('right'));
    $this->assertCount(1, $summary);
    $this->assertSame('Image position', (string) $summary[0]['label']);
    $this->assertSame('Right', (string) $summary[0]['value']);
  }

  /**
   * The view adds a class only for the right position.
   */
  public function testView(): void {
    $display = $this->createMock(EntityViewDisplayInterface::class);

    $build = [];
    $this->behavior->view($build, $this->mockParagraph('right'), $display, 'default');
    $this->assertSame(['csp-faux-card-image-right'], $build['#attributes']['class']);

    // Existing classes are preserved.
    $build = ['#attributes' => ['class' => ['existing']]];
    $this->behavior->view($build, $this->mockParagraph('right'), $display, 'default');
    $this->assertSame(['existing', 'csp-faux-card-image-right'], $build['#attributes']['class']);

    foreach ([NULL, 'left', 'bogus'] as $stored) {
      $build = [];
      $this->behavior->view($build, $this->mockParagraph($stored), $display, 'default');
      $this->assertSame([], $build);
    }
  }

  /**
   * Builds a paragraph mock with the given stored image position.
   *
   * A NULL value simulates a paragraph with no stored setting, in which case
   * the caller's default is returned, as Paragraph::getBehaviorSetting() does.
   */
  private function mockParagraph(mixed $image_position): Paragraph {
    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function ($plugin_id, $key, $default = NULL) use ($image_position) {
        if ($plugin_id === 'csp_faux_card_styles' && $key === 'image_position' && $image_position !== NULL) {
          return $image_position;
        }
        return $default;
      });
    return $paragraph;
  }

}
