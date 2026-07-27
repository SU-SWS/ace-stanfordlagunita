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
 * Tests that the CSP layout subclasses add the "Ultra slim" margin option.
 */
#[CoversClass(CspOneColumn::class)]
#[CoversClass(CspTwoColumn::class)]
#[CoversClass(CspThreeColumn::class)]
class UltraSlimMarginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'layout_builder'];

  /**
   * Provides each CSP layout subclass with the args needed to instantiate it.
   *
   * @return array
   *   Sets of [class, plugin_id, plugin_definition].
   */
  public static function layoutProvider(): array {
    return [
      'one column' => [
        CspOneColumn::class,
        'layout_paragraphs_1_column',
        ['regions' => ['main' => ['label' => 'Main']]],
      ],
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
   * The configuration form exposes the "Ultra slim" bottom_margin option.
   */
  #[DataProvider('layoutProvider')]
  public function testUltraSlimOptionAdded(string $class, string $plugin_id, array $definition): void {
    $layout = new $class([], $plugin_id, $definition);
    $form = $layout->buildConfigurationForm([], new FormState());

    $this->assertArrayHasKey('bottom_margin', $form);
    $this->assertArrayHasKey('ultra-slim', $form['bottom_margin']['#options']);
    $this->assertEquals('Ultra slim', (string) $form['bottom_margin']['#options']['ultra-slim']);
    // The upstream "None" option is preserved alongside the new one.
    $this->assertArrayHasKey('none', $form['bottom_margin']['#options']);
    // A helper description is set for editors.
    $this->assertNotEmpty((string) $form['bottom_margin']['#description']);
  }

  /**
   * A previously-saved "ultra-slim" value round-trips through submit.
   */
  #[DataProvider('layoutProvider')]
  public function testUltraSlimValuePersists(string $class, string $plugin_id, array $definition): void {
    $layout = new $class([], $plugin_id, $definition);

    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', '');
    $form_state->setValue('bottom_padding', '');
    $form_state->setValue('bottom_margin', 'ultra-slim');
    $layout->submitConfigurationForm($form, $form_state);

    $this->assertEquals('ultra-slim', $layout->getConfiguration()['bottom_margin']);
  }

}
