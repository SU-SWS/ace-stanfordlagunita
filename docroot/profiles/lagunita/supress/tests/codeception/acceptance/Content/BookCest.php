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
   *
   */
  public function testBookContentType(AcceptanceTester $I) {
    // content type exists
    $I->logInWithRole('administrator');
    $I->amOnPage('/node/add/sup_book');
    $I->see('Create Book');
    // check the fields
    $I->see('Subtitle');
    $I->see('Work ID');
    $I->see('Author Information');
    $I->see('Author 1');
    $I->see('Author 2');
    $I->see('Author 3');
    $I->see('Author 4');
    $I->see('Authors (full)');
    $I->see('Author Info');
    $I->see('Catalog Month');
    $I->see('Catalog Page');
    $I->see('Catalog Season');
    $I->see('Price Cloth');
    $I->see('Price Paper');
    $I->see('Price Digital');
    $I->see('Cloth Sale Percent');
    $I->see('Cloth Sale Price');
    $I->see('Paper Sale Percent');
    $I->see('Paper Sale Price');
    $I->see('Web Code Cloth');
    $I->see('Web Code Paper');
    $I->see('Description');
    $I->see('Availability Description');
    $I->see('Copublisher Name');
    $I->see('Copublisher?');
    $I->see('Copyright Year');
    $I->see('Digital Comp Link?');
    $I->see('HTML Page Title');
    $I->see('Illustrations');
    $I->see('Imprint');
    $I->see('In-Print Status');
    $I->see('Instructor Recommended');
    $I->see('ISBN 13 Cloth');
    $I->see('ISBN 13 Paper');
    $I->see('ISBN 13 Digital');
    $I->see('ISBN 13 Alternative');
    $I->see('ISBN 13 ISW');
    $I->see('Local Web Blurb');
    $I->see('New Paperback?');
    $I->see('Pages');
    $I->see('Print Desk Copies');
    $I->see('Status Cloth');
    $I->see('Status Paper');
    $I->see('Status Digital');
    $I->see('Publication Date Cloth');
    $I->see('Publication Date Paper');
    $I->see('Publication Date Digital');
    $I->see('Publication Date First');
    $I->see('Publication Year First');
    $I->see('Reviews');
    $I->see('Rights Description');
    $I->see('Sales Rank');
    $I->see('Series');
    $I->see('Table of Contents');
    $I->see('URL ISW');
    $I->see('Awards');
    $I->see('Imprint');
    $I->see('Series');
    $I->see('Related Titles');
    $I->see('Book Image');
  }

  /**
   * Ensure the custom taxonomies exist.
   */
  public function testBookTaxonomiesExist(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
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
