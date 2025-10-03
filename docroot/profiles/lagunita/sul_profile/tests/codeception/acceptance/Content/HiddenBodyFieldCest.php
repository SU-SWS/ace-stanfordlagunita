<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Test that body field is hidden from stanford_page and stanford_news content types.
 *
 * This test verifies that the body field, which exists in upstream stanford_profile,
 * is properly hidden in the library site's content editing forms.
 */
#[CodeceptionAttribute\Group('library')]
#[CodeceptionAttribute\Group('content')]
#[CodeceptionAttribute\Group('form-display')]
class HiddenBodyFieldCest {

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
   * Test that body field is hidden on stanford_page content type.
   */
  public function testBodyFieldHiddenOnStanfordPage(AcceptanceTester $I) {
    // Create a stanford_page node.
    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(3, TRUE),
    ]);

    // Log in as a contributor who can edit content.
    $I->logInWithRole('contributor');

    // Navigate to the node edit form.
    $I->amOnPage($node->toUrl('edit-form')->toString());

    // Verify we're on the edit page.
    $I->canSeeResponseCodeIs(200);

    // The body field should NOT be visible on the form.
    // Check that the field wrapper doesn't exist or is hidden.
    $I->dontSeeElement('input[name="body[0][value]"]');
    $I->dontSeeElement('textarea[name="body[0][value]"]');

  }

  /**
   * Test that body field is hidden on stanford_news content type.
   */
  public function testBodyFieldHiddenOnStanfordNews(AcceptanceTester $I) {
    // Create a stanford_news node.
    $node = $I->createEntity([
      'type' => 'stanford_news',
      'title' => $this->faker->words(3, TRUE),
    ]);

    // Log in as a contributor who can edit content.
    $I->logInWithRole('contributor');

    // Navigate to the node edit form.
    $I->amOnPage($node->toUrl('edit-form')->toString());

    // Verify we're on the edit page.
    $I->canSeeResponseCodeIs(200);

    // The body field should NOT be visible on the form.
    // Check that the field wrapper doesn't exist or is hidden.
    $I->dontSeeElement('input[name="body[0][value]"]');
    $I->dontSeeElement('textarea[name="body[0][value]"]');
    
  }

  /**
   * Test that body field is hidden when creating new stanford_page.
   */
  public function testBodyFieldHiddenOnNewStanfordPage(AcceptanceTester $I) {
    // Log in as a contributor who can create content.
    $I->logInWithRole('contributor');

    // Navigate to the node creation form.
    $I->amOnPage('/node/add/stanford_page');

    // Verify we're on the creation page.
    $I->canSeeResponseCodeIs(200);

    // The body field should NOT be visible on the form.
    $I->dontSeeElement('input[name="body[0][value]"]');
    $I->dontSeeElement('textarea[name="body[0][value]"]');

    // Verify that other expected fields ARE visible.
    $I->canSeeElement('input[name="title[0][value]"]');
  }

  /**
   * Test that body field is hidden when creating new stanford_news.
   */
  public function testBodyFieldHiddenOnNewStanfordNews(AcceptanceTester $I) {
    // Log in as a contributor who can create content.
    $I->logInWithRole('contributor');

    // Navigate to the node creation form.
    $I->amOnPage('/node/add/stanford_news');

    // Verify we're on the creation page.
    $I->canSeeResponseCodeIs(200);

    // The body field should NOT be visible on the form.
    $I->dontSeeElement('input[name="body[0][value]"]');
    $I->dontSeeElement('textarea[name="body[0][value]"]');

    // Verify that other expected fields ARE visible.
    $I->canSeeElement('input[name="title[0][value]"]');
  }

}
