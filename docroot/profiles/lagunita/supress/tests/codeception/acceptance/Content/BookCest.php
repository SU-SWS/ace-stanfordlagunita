<?php

use Faker\Factory;

/**
 * Class BookCest.
 *
 * @group content
 * @group sup_book
 */
class BookCest {

  /**
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test Constructor
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Ensure the content type exist and has its fields
   */
  public function testBookContentType(AcceptanceTester $I) {
    // content type exists
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/sup_book');
    $I->see('Create Book');
    // check the fields
    $I->see('Author Info');
    $I->see('Authors (full)');
    $I->see('Availability Description');
    $I->see('Awards');
    $I->see('Book Authors');
    $I->see('Book Image');
    $I->see('Catalog Season');
    $I->see('Cloth Sale Percent');
    $I->see('Cloth Sale Price');
    $I->see('Copublisher Name');
    $I->see('Description');
    $I->see('Digital Comp Link?');
    $I->see('ISBN 13 Alternative');
    $I->see('ISBN 13 Cloth');
    $I->see('ISBN 13 Digital');
    $I->see('ISBN 13 ISW');
    $I->see('ISBN 13 Paper');
    $I->see('Imprint');
    $I->see('Imprint');
    $I->see('Pages');
    $I->see('Paper Sale Percent');
    $I->see('Paper Sale Price');
    $I->see('Price Cloth');
    $I->see('Price Paper');
    $I->see('Print Desk Copies');
    $I->see('Publication Date Cloth');
    $I->see('Publication Date First');
    $I->see('Publication Year First');
    $I->see('Related Titles');
    $I->see('Reviews');
    $I->see('Sales Rank');
    $I->see('Series');
    $I->see('Series');
    $I->see('Subtitle');
    $I->see('URL ISW');
    $I->see('Work ID');
  }

  /**
   * Ensure the custom taxonomies exist.
   */
  public function testBookTaxonomiesExist(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/admin/structure/taxonomy');
    $I->see('Book Subjects');
    $I->see('Imprints');
    $I->see('Series');
    $I->amOnPage('/admin/structure/taxonomy/manage/sup_book_subjects/overview');
    $I->see('Add Term');
    $I->amOnPage('/admin/structure/taxonomy/manage/sup_imprints/overview');
    $I->see('Add Term');
    $I->amOnPage('/admin/structure/taxonomy/manage/sup_series/overview');
    $I->see('Add Term');
  }

}
