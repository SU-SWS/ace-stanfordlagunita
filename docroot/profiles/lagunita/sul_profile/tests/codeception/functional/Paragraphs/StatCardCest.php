<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Stat card paragraph tests.
 */
#[CodeceptionAttribute\Group('paragraphs')]
#[CodeceptionAttribute\Group('stat_card')]
class StatCardCest {

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
   * Test stat field character limit validation through UI.
   */
  public function testStatFieldCharacterLimitValidation(FunctionalTester $I) {
    $I->logInWithRole('site_manager');
    
    // Create initial paragraph with stat card
    $paragraph = $I->createEntity([
      'type' => 'stanford_stat_card',
      'su_stat_stat' => '12345', // Start with valid value
      'su_stat_headline' => $this->faker->words(3, TRUE),
      'su_stat_bg_color' => '#ffffff',
    ], 'paragraph');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(4, TRUE),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);

    // Edit the stat card through LPB controls
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->scrollTo('.js-lpb-component', 0, -100);
    $I->moveMouseOver('.js-lpb-component', 10, 10);
    $I->click('Edit', '.lpb-controls');
    $I->waitForText('Stat');
    
    // Try to enter more than 12 characters
    $long_stat = '1234567890123'; // 13 characters
    $I->fillField('[name*="su_stat_stat"]', $long_stat);
    
    // Save the component
    $I->click('Save', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.ui-dialog');
    $I->waitForText('123,456,789,012');
    $I->see('123,456,789,012'); // First 12 characters
    $I->dontSee('1234567890123'); // Full 13 characters
  }

  /**
   * Test background color options include white.
   */
  // public function testBackgroundColorWhiteOption(FunctionalTester $I) {
  //   $I->logInWithRole('site_manager');
    
  //   // Create initial paragraph with stat card
  //   $paragraph = $I->createEntity([
  //     'type' => 'stanford_stat_card',
  //     'su_stat_stat' => '42',
  //     'su_stat_headline' => $this->faker->words(3, TRUE),
  //     'su_stat_bg_color' => '#000000', // Start with non-white
  //   ], 'paragraph');

  //   $node = $I->createEntity([
  //     'type' => 'stanford_page',
  //     'title' => $this->faker->words(4, TRUE),
  //     'su_page_components' => [
  //       'target_id' => $paragraph->id(),
  //       'entity' => $paragraph,
  //     ],
  //   ]);

  //   // Edit the stat card through LPB controls
  //   $I->amOnPage($node->toUrl('edit-form')->toString());
  //   $I->scrollTo('.js-lpb-component', 0, -100);
  //   $I->moveMouseOver('.js-lpb-component', 10, 10);
  //   $I->click('Edit', '.lpb-controls');
  //   $I->scrollTo('.claro-details__summary', 0, -100);
  //   $I->click('summary[role="button"]');
  //   $I->scrollTo('.field--type-color-field-type', 0, -100);
  //   $I->waitForText('Background color');
    
  //   // Verify white option exists
  //   $I->seeElement('button[value="#ffffff"]');
    
  //   // Select white background
  //   $I->click('button[value="#ffffff"]');
    
  //   // Save the component
  //   $I->click('Save', '.ui-dialog-buttonset');
  //   $I->waitForElementNotVisible('.ui-dialog');
    
  //   // Verify white background is applied
  //   $I->seeElement('.su-stat-card[style*="background-color: #ffffff"]');
  // }
}