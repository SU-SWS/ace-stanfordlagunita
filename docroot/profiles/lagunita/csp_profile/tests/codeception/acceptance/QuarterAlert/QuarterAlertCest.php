<?php

use Codeception\Attribute as CodeceptionAttribute;

/**
 * Test for the Quarter Alert config page.
 */
#[CodeceptionAttribute\Group('quarter-alert')]
class QuarterAlertCest {

  /**
   * Delete the config page after finishing.
   */
  public function _after(AcceptanceTester $I) {
    $config_page = \Drupal::entityTypeManager()
      ->getStorage('config_pages')
      ->load('csp_quarter_alert');
    if ($config_page) {
      $config_page->delete();
    }
  }

  /**
   * Test the form exists.
   */
  public function testFormExists(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/system/quarter-alert');
    $I->canSee('Edit config page Quarter Alert');
  }

  /**
   * Test the form settings.
   */
  public function testFormSettings(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/config/system/quarter-alert');
    $I->checkOption('#edit-csp-qa-enabled-value');
    $I->fillField('Quarter Label', 'WINTER QUARTER 2025');
    $I->fillField('#edit-csp-qa-text-0-value', '<p>Registration is now open.</p>');
    $I->selectOption('#edit-csp-qa-color', 'cardinal');
    $I->click('Save');
    $I->see('Quarter Alert has been', '.messages-list');

    // TODO: Once the Next.js frontend component is implemented, assert the
    // rendered block appears on the homepage with the correct CSS classes,
    // label text, and alert text. See GlobalMessageCest::testFormSettings()
    // for the pattern to follow.
  }

  /**
   * Test administrator role permissions.
   */
  public function testAdminUserRole(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/system/quarter-alert');
    $I->canSeeResponseCodeIs(200);
    $I->canSee('Edit config page Quarter Alert');
  }

  /**
   * Test site_manager role permissions.
   */
  public function testSiteManagerUserRole(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/config/system/quarter-alert');
    $I->canSeeResponseCodeIs(200);
    $I->canSee('Edit config page Quarter Alert');
  }

  /**
   * Test site_editor role permissions.
   */
  public function testSiteEditorUserRole(AcceptanceTester $I) {
    $I->logInWithRole('site_editor');
    $I->amOnPage('/admin/config/system/quarter-alert');
    $I->canSeeResponseCodeIs(403);
    $I->cantSee('Edit config page Quarter Alert');
  }

  /**
   * Test contributor role permissions.
   */
  public function testContributorUserRole(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/admin/config/system/quarter-alert');
    $I->canSeeResponseCodeIs(403);
    $I->cantSee('Edit config page Quarter Alert');
  }

}
