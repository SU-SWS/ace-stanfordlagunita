<?php

namespace Drupal\Tests\sul_helper\Unit\Layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sul_helper\Layouts\LayoutWithHeading;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the LayoutWithHeading trait.
 *
 * @coversDefaultClass \Drupal\sul_helper\Layouts\LayoutWithHeading
 * @group sul_helper
 */
class LayoutWithHeadingTest extends UnitTestCase {

  /**
   * Mock class using the LayoutWithHeading trait.
   *
   * @var object
   */
  protected $mockLayout;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a mock class that uses the trait.
    $this->mockLayout = new class {
      use LayoutWithHeading;

      public $configuration = [];

      public function t($string) {
        return $string;
      }

      // Expose protected methods for testing.
      public function publicAddHeadingElement(array &$form, $form_state) {
        return $this->addHeadingElement($form, $form_state);
      }

      public function publicSubmitHeadingForm(array &$form, $form_state) {
        return $this->submitHeadingForm($form, $form_state);
      }
    };
  }

  /**
   * Tests the addHeadingElement method.
   *
   * @covers ::addHeadingElement
   */
  public function testAddHeadingElement() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Test with empty configuration.
    $result = $this->mockLayout->publicAddHeadingElement($form, $form_state);

    // Verify heading field exists.
    $this->assertArrayHasKey('heading', $result);
    $this->assertEquals('textfield', $result['heading']['#type']);
    $this->assertEquals('Section Heading', $result['heading']['#title']);
    $this->assertEquals('', $result['heading']['#default_value']);
    $this->assertEquals(255, $result['heading']['#maxlength']);

    // Verify heading_level field exists.
    $this->assertArrayHasKey('heading_level', $result);
    $this->assertEquals('select', $result['heading_level']['#type']);
    $this->assertEquals('Heading Level', $result['heading_level']['#title']);
    $this->assertEquals('h2', $result['heading_level']['#default_value']);
    $this->assertArrayHasKey('h2', $result['heading_level']['#options']);
    $this->assertArrayHasKey('h3', $result['heading_level']['#options']);
    $this->assertArrayHasKey('h4', $result['heading_level']['#options']);
    $this->assertArrayHasKey('h5', $result['heading_level']['#options']);
    $this->assertArrayHasKey('h6', $result['heading_level']['#options']);

    // Verify states for conditional visibility.
    $this->assertArrayHasKey('#states', $result['heading_level']);
    $this->assertArrayHasKey('visible', $result['heading_level']['#states']);

    // Verify display_heading_gradient checkbox exists.
    $this->assertArrayHasKey('display_heading_gradient', $result);
    $this->assertEquals('checkbox', $result['display_heading_gradient']['#type']);
    $this->assertFalse($result['display_heading_gradient']['#default_value']);
  }

  /**
   * Tests the addHeadingElement method with existing configuration.
   *
   * @covers ::addHeadingElement
   */
  public function testAddHeadingElementWithConfiguration() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Set existing configuration.
    $this->mockLayout->configuration = [
      'heading' => 'Test Heading',
      'heading_level' => 'h3',
      'display_heading_gradient' => TRUE,
    ];

    $result = $this->mockLayout->publicAddHeadingElement($form, $form_state);

    // Verify default values come from configuration.
    $this->assertEquals('Test Heading', $result['heading']['#default_value']);
    $this->assertEquals('h3', $result['heading_level']['#default_value']);
    $this->assertTrue($result['display_heading_gradient']['#default_value']);
  }

  /**
   * Tests the submitHeadingForm method.
   *
   * @covers ::submitHeadingForm
   */
  public function testSubmitHeadingForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Mock form state getValue method.
    $form_state->expects($this->exactly(3))
      ->method('getValue')
      ->willReturnMap([
        ['heading', 'My Section Heading'],
        ['heading_level', 'h4'],
        ['display_heading_gradient', TRUE],
      ]);

    $this->mockLayout->publicSubmitHeadingForm($form, $form_state);

    // Verify configuration was set.
    $this->assertEquals('My Section Heading', $this->mockLayout->configuration['heading']);
    $this->assertEquals('h4', $this->mockLayout->configuration['heading_level']);
    $this->assertTrue($this->mockLayout->configuration['display_heading_gradient']);
  }

  /**
   * Tests that heading level options are correct.
   *
   * @covers ::addHeadingElement
   */
  public function testHeadingLevelOptions() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = $this->mockLayout->publicAddHeadingElement($form, $form_state);

    $expected_options = ['h2', 'h3', 'h4', 'h5', 'h6'];
    $actual_options = array_keys($result['heading_level']['#options']);

    $this->assertEquals($expected_options, $actual_options);
  }

}
