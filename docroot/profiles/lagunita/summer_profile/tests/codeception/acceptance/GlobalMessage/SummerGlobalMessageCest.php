<?php

use Faker\Factory;

/**
 * @group summer-global-message
 */
class SummerGlobalMessageCest {

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  public function __construct() {
    $this->faker = Factory::create();
  }

  public function _after(AcceptanceTester $I) {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('summer_entity')
      ->loadMultiple();
    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  public function testAccess(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/admin/content/summer/add/global_msg');
    $I->canSeeResponseCodeIs(403);

    $I->amOnPage('/user/logout');
    $I->click('Log out', 'form');

    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/content/summer/add/global_msg');
    $I->canSeeInField('Label', '');
    $I->canSeeInField('Message', '');
    $I->canSeeInField('URL', '');
    $I->canSeeInField('Link text', '');
    $I->canSeeInField('Hide on Pages', '');
    $I->canSee('Scheduling options');
  }

  public function testExistingPublishedValidation(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/content/summer/add/global_msg');

    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->checkOption('Published');
    $I->click('Save');
    $I->canSee('has been created.');

    $I->amOnPage('/admin/content/summer/add/global_msg');
    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->checkOption('Published');
    $I->click('Save');
    $I->canSee('The published dates are invalid.');
  }

  public function testUnpublishScheduledMessage(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/content/summer/add/global_msg');

    $publish_time = time() + 60 * 60 * 24;

    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $publish_time));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $publish_time));

    $I->click('Save');
    $I->canSee('has been created.');

    $I->amOnPage('/admin/content/summer/add/global_msg');

    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->fillField('unpublish_on[0][value][date]', date('Y-m-d', $publish_time));
    $I->fillField('unpublish_on[0][value][time]', date('H:i:s', $publish_time + 60));

    $I->click('Save');
    $I->cantSee('has been created.');
    $I->canSee('The published dates are invalid.');

    $I->fillField('unpublish_on[0][value][time]', date('H:i:s', $publish_time - 60));

    $I->click('Save');
    $I->canSee('has been created.');
  }

  public function testPublishScheduledMessage(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/content/summer/add/global_msg');

    $publish_time = time() + 60 * 60 * 24;

    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->fillField('unpublish_on[0][value][date]', date('Y-m-d', $publish_time));
    $I->fillField('unpublish_on[0][value][time]', date('H:i:s', $publish_time));

    $I->click('Save');
    $I->canSee('has been created.');

    $I->amOnPage('/admin/content/summer/add/global_msg');

    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->uncheckOption('Published');
    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $publish_time));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $publish_time - 60));

    $I->click('Save');
    $I->cantSee('has been created.');
    $I->canSee('The published dates are invalid.');

    $I->fillField('publish_on[0][value][time]', date('H:i:s', $publish_time + 60));

    $I->click('Save');
    $I->canSee('has been created.');
  }

  public function testBothScheduledMessage(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/content/summer/add/global_msg');

    $publish_time = time() + 60 * 60 * 24;
    $unpublish_time = time() + 10 * 60 * 60 * 24;

    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->uncheckOption('Published');
    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $publish_time));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $publish_time));
    $I->fillField('unpublish_on[0][value][date]', date('Y-m-d', $unpublish_time));
    $I->fillField('unpublish_on[0][value][time]', date('H:i:s', $unpublish_time));

    $I->click('Save');
    $I->canSee('has been created.');

    // Before the original message.
    $I->amOnPage('/admin/content/summer/add/global_msg');
    $I->fillField('Label', $this->faker->words(4, TRUE));

    $I->click('Save');
    $I->cantSee('has been created.');
    $I->canSee('The published dates are invalid.');

    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $publish_time - 60));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $publish_time));
    $I->click('Save');
    $I->cantSee('has been created.');
    $I->canSee('The published dates are invalid.');

    $I->checkOption('Published');
    $I->fillField('publish_on[0][value][date]', '');
    $I->fillField('publish_on[0][value][time]', '');
    $I->fillField('unpublish_on[0][value][date]', date('Y-m-d', $publish_time));
    $I->fillField('unpublish_on[0][value][time]', date('H:i:s', $publish_time));
    $I->click('Save');
    $I->canSee('has been created.');

    // After the original message.
    $I->amOnPage('/admin/content/summer/add/global_msg');
    $I->fillField('Label', $this->faker->words(4, TRUE));

    $I->click('Save');
    $I->cantSee('has been created.');
    $I->canSee('The published dates are invalid.');

    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $publish_time - 60));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $publish_time));
    $I->click('Save');
    $I->cantSee('has been created.');
    $I->canSee('The published dates are invalid.');

    $I->checkOption('Published');
    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $unpublish_time));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $unpublish_time));
    $I->fillField('unpublish_on[0][value][date]', date('Y-m-d', $unpublish_time + 120));
    $I->fillField('unpublish_on[0][value][time]', date('H:i:s', $unpublish_time + 120));
    $I->click('Save');
    $I->canSee('has been created.');

    // After the previous
    $I->amOnPage('/admin/content/summer/add/global_msg');
    $I->fillField('Label', $this->faker->words(4, TRUE));
    $I->checkOption('Published');

    $I->fillField('publish_on[0][value][date]', date('Y-m-d', $unpublish_time + 120));
    $I->fillField('publish_on[0][value][time]', date('H:i:s', $unpublish_time + 120));
    $I->click('Save');
    $I->canSee('has been created.');
  }

}
