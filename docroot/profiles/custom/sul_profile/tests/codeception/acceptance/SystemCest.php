<?php

/**
 * System tests.
 *
 * @group system
 */
class SystemCest {

  /**
   * Test the site status report.
   */
  public function testSiteStatus(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/reports/status');
    $I->canSee('10.1', '.system-status-general-info');

    if (\Drupal::moduleHandler()->moduleExists('chosen')) {
      $I->canSee('Chosen Javascript file');
      $I->cantSee('Chosen JavaScript file', '.system-status-report__status-icon--error');
    }
  }

}
