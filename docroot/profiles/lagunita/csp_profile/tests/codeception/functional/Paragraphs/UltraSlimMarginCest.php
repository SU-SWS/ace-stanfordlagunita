<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Tests the CSP "Ultra slim" option on a section's "Space below section" list.
 */
#[CodeceptionAttribute\Group('paragraphs')]
#[CodeceptionAttribute\Group('ultra-slim-margin')]
class UltraSlimMarginCest {

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * The "Ultra slim" option is selectable in the UI and applied on render.
   */
  public function testUltraSlimOptionAvailable(FunctionalTester $I) {
    // A one-column section with a text component so the section renders in the
    // Layout Paragraphs builder and can be edited.
    /** @var \Drupal\paragraphs\ParagraphInterface $layout */
    $layout = $I->createEntity(['type' => 'stanford_layout'], 'paragraph');
    $layout->setBehaviorSettings('layout_paragraphs', [
      'layout' => 'layout_paragraphs_1_column',
      'config' => [
        'bg_color' => '',
        'bottom_margin' => NULL,
        'bottom_padding' => NULL,
        'top_padding' => NULL,
      ],
    ]);
    $layout->save();

    /** @var \Drupal\paragraphs\ParagraphInterface $wysiwyg */
    $wysiwyg = $I->createEntity([
      'type' => 'stanford_wysiwyg',
      'su_wysiwyg_text' => [
        'value' => $this->faker->sentence(),
        'format' => 'stanford_html',
      ],
    ], 'paragraph');
    $wysiwyg->setBehaviorSettings('layout_paragraphs', [
      'parent_uuid' => $layout->uuid(),
      'region' => 'main',
    ]);
    $wysiwyg->save();

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(3, TRUE),
      'su_page_components' => [
        ['target_id' => $layout->id(), 'entity' => $layout],
        ['target_id' => $wysiwyg->id(), 'entity' => $wysiwyg],
      ],
    ]);

    $I->logInWithRole('contributor');
    $I->amOnPage($node->toUrl('edit-form')->toString());

    $I->waitForElementVisible('.js-lpb-component');
    $I->moveMouseOver('.js-lpb-component', 10, 10);
    $I->click('.lpb-layout a.lpb-edit');

    $I->waitForText('Space below section');
    $I->selectOption('Space below section', 'Ultra slim');
    $I->seeOptionIsSelected('Space below section', 'Ultra slim');
    $I->click('Save', '.ui-dialog-buttonpane');
    $I->waitForElementNotVisible('.ui-dialog');

    // Save the node and confirm the ultra-slim margin was applied on render.
    $I->click('Save', '#edit-actions');
    $I->canSee($node->label(), 'h1');
    $I->canSeeElement('.bottom-margin-ultra-slim');
  }

}
