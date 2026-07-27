<?php

declare(strict_types=1);

namespace Drupal\Tests\csp_helper\Kernel\Layouts;

use Drupal\Core\Form\FormState;
use Drupal\csp_helper\Layouts\CspOneColumn;
use Drupal\csp_helper\Layouts\CspThreeColumn;
use Drupal\csp_helper\Layouts\CspTwoColumn;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the CSP "Section width" option on the multi-column layout subclasses.
 */
#[CoversClass(CspTwoColumn::class)]
#[CoversClass(CspThreeColumn::class)]
class SectionWidthTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'layout_builder'];

  /**
   * Provides the multi-column CSP layouts that expose the section width field.
   *
   * @return array
   *   Sets of [class, plugin_id, plugin_definition].
   */
  public static function multiColumnProvider(): array {
    return [
      'two column' => [
        CspTwoColumn::class,
        'layout_paragraphs_2_column',
        ['regions' => ['left' => ['label' => 'Left'], 'right' => ['label' => 'Right']]],
      ],
      'three column' => [
        CspThreeColumn::class,
        'layout_paragraphs_3_column',
        [
          'regions' => [
            'left' => ['label' => 'Left'],
            'main' => ['label' => 'Main'],
            'right' => ['label' => 'Right'],
          ],
        ],
      ],
    ];
  }

  /**
   * Multi-column layouts expose a "Section width" select defaulting to "Full".
   */
  #[DataProvider('multiColumnProvider')]
  public function testSectionWidthOptionAdded(string $class, string $plugin_id, array $definition): void {
    $layout = new $class([], $plugin_id, $definition);
    $form = $layout->buildConfigurationForm([], new FormState());

    $this->assertArrayHasKey('section_width', $form);
    $this->assertSame('select', $form['section_width']['#type']);
    $this->assertSame(
      ['full', 'wide', 'standard'],
      array_keys($form['section_width']['#options']),
    );
    // Default value is "Full" so existing sections are unaffected.
    $this->assertSame('full', $form['section_width']['#default_value']);
  }

  /**
   * A previously-saved value is reflected as the select's default.
   */
  #[DataProvider('multiColumnProvider')]
  public function testSectionWidthDefaultReflectsConfig(string $class, string $plugin_id, array $definition): void {
    $layout = new $class(['section_width' => 'standard'], $plugin_id, $definition);
    $form = $layout->buildConfigurationForm([], new FormState());

    $this->assertSame('standard', $form['section_width']['#default_value']);
  }

  /**
   * The default configuration seeds "full" so new sections opt out of capping.
   */
  #[DataProvider('multiColumnProvider')]
  public function testSectionWidthDefaultConfiguration(string $class, string $plugin_id, array $definition): void {
    $layout = new $class([], $plugin_id, $definition);

    $this->assertSame('full', $layout->getConfiguration()['section_width']);
  }

  /**
   * A submitted section width round-trips into the layout configuration.
   */
  #[DataProvider('multiColumnProvider')]
  public function testSectionWidthValuePersists(string $class, string $plugin_id, array $definition): void {
    $layout = new $class([], $plugin_id, $definition);

    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', '');
    $form_state->setValue('bottom_padding', '');
    $form_state->setValue('bottom_margin', '');
    $form_state->setValue('section_width', 'wide');
    $layout->submitConfigurationForm($form, $form_state);

    $this->assertSame('wide', $layout->getConfiguration()['section_width']);
  }

  /**
   * An empty submitted value falls back to "full".
   */
  public function testEmptySectionWidthFallsBackToFull(): void {
    $layout = new CspTwoColumn([], 'layout_paragraphs_2_column', ['regions' => ['left' => ['label' => 'Left'], 'right' => ['label' => 'Right']]]);

    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', '');
    $form_state->setValue('bottom_padding', '');
    $form_state->setValue('bottom_margin', '');
    $form_state->setValue('section_width', '');
    $layout->submitConfigurationForm($form, $form_state);

    $this->assertSame('full', $layout->getConfiguration()['section_width']);
  }

  /**
   * Single-column sections do NOT get a section width field (out of scope).
   */
  public function testSingleColumnHasNoSectionWidth(): void {
    $layout = new CspOneColumn([], 'layout_paragraphs_1_column', ['regions' => ['main' => ['label' => 'Main']]]);
    $form = $layout->buildConfigurationForm([], new FormState());

    $this->assertArrayNotHasKey('section_width', $form);
  }

}
