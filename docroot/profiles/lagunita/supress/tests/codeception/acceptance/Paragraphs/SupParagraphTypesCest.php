<?php

/**
 * Test carousel paragraph type.
 *
 * @group sup
 * @group sup_paragraphs
 */
class SupParagraphTypesCest {

  public function testSupParagraphTypes(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure/paragraphs_type');
    $I->canSee('sup_carousel');
    $I->canSee('sup_file_list');
    $I->canSee('Blog Teasers');
    $I->canSee('Blog Item');
    $I->canSee('sup_carousel_slide');
  }

}