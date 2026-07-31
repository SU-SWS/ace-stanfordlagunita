<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Tests the CSP "Poster" card variant added by csp_helper (CSP-115).
 */
#[CodeceptionAttribute\Group('paragraphs')]
#[CodeceptionAttribute\Group('poster-card')]
class PosterCardCest {

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
   * Selecting the Poster variant renders the variant and bg-color classes.
   *
   */
  public function testPosterVariantRenders(FunctionalTester $I) {
    $header = $this->faker->words(3, TRUE);
    $body = $this->faker->words(6, TRUE);

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $I->createEntity([
      'type' => 'stanford_card',
      'su_card_header' => $header,
      'su_card_body' => $body,
    ], 'paragraph');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(4, TRUE),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);

    $I->logInWithRole('site_manager');

    // Open the card in the Layout Paragraphs builder.
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->scrollTo('.js-lpb-component', 0, -100);
    $I->moveMouseOver('.js-lpb-component', 10, 10);
    $I->click('Edit', '.lpb-controls');
    $I->waitForText('Behaviors');
    $I->clickWithLeftButton('.lpb-behavior-plugins summary');

    // Choose the Poster variant. This is only available because the behavior
    // class was swapped to CspCardBehaviors.
    $I->selectOption('Card variant', 'Poster');

    // The #states rule reveals the "Poster background color" field once Poster
    // is selected; its box widget renders a swatch per configured color. Pick
    // Stanford purple (#620059).
    $I->waitForElementVisible('button.color_field_widget_box__square[color="#620059"]');
    $I->click('button.color_field_widget_box__square[color="#620059"]');

    $I->click('Save', '.ui-dialog-buttonpane');
    $I->waitForElementNotVisible('.ui-dialog');

    // Save the node and confirm the variant was applied on render.
    $I->click('Save', '#edit-actions');
    $I->canSee($node->label(), 'h1');
    $I->canSeeElement('.csp-card-variant-poster');
    $I->canSeeElement('.csp-card-variant-poster.csp-card-bg-620059');
  }

}
