<?php

declare(strict_types=1);

namespace Drupal\Tests\csp_helper\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\csp_helper\Plugin\paragraphs\Behavior\CspFauxCourseCardBehaviors;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the Faux Course Card heading level behavior plugin.
 */
#[CoversClass(CspFauxCourseCardBehaviors::class)]
class CspFauxCourseCardBehaviorsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * The plugin should only be offered on the Faux Course Card type.
   */
  public function testAppliesToFauxCourseCardOnly(): void {
    $faux_course_card = $this->createMock(ParagraphsType::class);
    $faux_course_card->method('id')->willReturn('csp_faux_course_card');
    $this->assertTrue(CspFauxCourseCardBehaviors::isApplicable($faux_course_card));

    $card = $this->createMock(ParagraphsType::class);
    $card->method('id')->willReturn('stanford_card');
    $this->assertFalse(CspFauxCourseCardBehaviors::isApplicable($card));
  }

  /**
   * The paragraph type stores H2 as the plugin's shipped default.
   */
  public function testHeadingDefaultsToH2(): void {
    $this->assertSame(['heading' => 'h2'], $this->behavior()->defaultConfiguration());
  }

  /**
   * Editors get a select limited to the two levels the card supports.
   */
  public function testHeadingSelectOffersH2AndH3(): void {
    $element = $this->buildForm([]);

    $this->assertArrayHasKey('heading', $element);
    $this->assertSame('select', $element['heading']['#type']);
    $this->assertSame(['h2', 'h3'], array_keys($element['heading']['#options']));
  }

  /**
   * An unset behavior setting should fall back to the H2 default.
   */
  public function testHeadingFormDefaultsToH2WhenUnset(): void {
    // A paragraph saved before this setting existed has no stored settings at all.
    $element = $this->buildForm([]);
    $this->assertSame('h2', $element['heading']['#default_value']);
  }

  /**
   * A stored heading should preselect its option.
   */
  public function testHeadingFormReflectsStoredValue(): void {
    $element = $this->buildForm(['csp_course_card_styles' => ['heading' => 'h3']]);
    $this->assertSame('h3', $element['heading']['#default_value']);
  }

  /**
   * Builds the behavior form against a paragraph with given stored settings.
   *
   * @param array $stored_settings
   *   Behavior settings, keyed by plugin id then setting name. The stub
   *   mirrors Paragraph::getBehaviorSetting(), returning the caller's default
   *   for anything absent, so an empty array exercises the fallback path.
   *
   * @return array
   *   The built behavior form element.
   */
  protected function buildForm(array $stored_settings): array {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(static fn ($plugin_id, $key, $default = NULL) => $stored_settings[$plugin_id][$key] ?? $default);

    $form = [];
    $form_state = new FormState();
    return $this->behavior()->buildBehaviorForm($paragraph, $form, $form_state);
  }

  /**
   * Instantiates the behavior plugin.
   *
   * @return \Drupal\csp_helper\Plugin\paragraphs\Behavior\CspFauxCourseCardBehaviors
   *   The plugin.
   */
  protected function behavior(): CspFauxCourseCardBehaviors {
    return new CspFauxCourseCardBehaviors([], '', [], $this->createMock(EntityFieldManagerInterface::class));
  }

}
