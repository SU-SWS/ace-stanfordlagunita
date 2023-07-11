<?php

use Faker\Factory;

/**
 * Branch page tests.
 *
 * @group sul-branch
 */
class BranchPageCest {

  /**
   * Faker generator.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Test creating a branch page.
   */
  public function testBranchPageCreate(FunctionalTester $I) {
    $title = $this->faker->words(3, TRUE);
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/sul_library');
    $I->fillField('Title', $title);
    $I->selectOption('Type of Location', 'Center');
    $I->cantSee('Branch Hours');
    $I->selectOption('Type of Location', 'Branch');
    $I->canSee('Branch Hours');
    $I->selectOption('Branch Hours', 'Cecil H. Green Library');
    $I->click('#edit-group-contact-details summary');
    $I->fillField('Contact Email', $this->faker->email);
    $I->fillField('Contact Phone Number', $this->faker->phoneNumber);
    $I->selectOption('Country', 'United States');
    $I->waitForText('Street address');
    $I->fillField('Street address', $this->faker->streetAddress);
    $I->fillField('City', $this->faker->city);
    $I->selectOption('[name="su_library__address[0][address][administrative_area]"]', 'California');
    $I->fillField('Zip code', $this->faker->postcode);
    $I->fillField('Map Link', $this->faker->url);

    $I->scrollTo('.field--name-sul-library__a11y', NULL, -100);
    $I->click('Link', '.ck-toolbar__items');
    $I->waitForText('Link URL');
    $ally_url = $this->faker->url;
    $I->fillField('Link URL', $ally_url);
    $I->click('Save', '.ck-link-form');

    $I->click('Add section');
    $I->waitForText('Create new Layout');
    $I->click('Save', '.ui-dialog-buttonset');
    $I->waitForElement('.lpb-btn--add');
    $I->click('Choose component');
    $I->waitForText('Choose a paragraph');;
    $I->click('Text Area', '.lpb-component-list');
    $I->waitForText('Create new Text Area');

    $paragraph_url = $this->faker->url;
    $I->click('Link', '.ui-dialog .ck-toolbar__items');
    $I->waitForText('Link URL');
    $I->fillField('Link URL', $paragraph_url);
    $I->click('Save', '.ck-link-form');
    $I->click('Save', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.ui-dialog');

    $I->click('Save');
    $I->canSee($title, 'h1');
    $I->canSeeLink($ally_url, $ally_url);
    $I->canSeeLink($paragraph_url, $paragraph_url);
  }

}
