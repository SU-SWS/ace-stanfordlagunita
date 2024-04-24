<?php

use Faker\Factory;

/**
 * Test carousel paragraph type.
 *
 * @group sup
 * @group sup_carousel
 */
class SupCarouselCest {

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

  public function testCarousel(AcceptanceTester $I) {
    $fields = [
      'Eyebrow' => $this->faker->words(3, TRUE),
      'Slide Title' => $this->faker->words(3, TRUE),
      'Subtitle' => $this->faker->words(3, TRUE),
      'Body' => $this->faker->paragraphs(3, TRUE),
      '[name="su_page_banner[0][subform][sup_carousel_slides][0][subform][sup_slide_button][0][uri]"]' => $this->faker->url,
      '[name="su_page_banner[0][subform][sup_carousel_slides][0][subform][sup_slide_button][0][title]"]' => $this->faker->words(3, TRUE),
    ];

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(3, TRUE),
    ]);
    $I->logInWithRole('contributor');
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->click('Add Carousel');
    $I->canSeeInField('Book', '');

    foreach ($fields as $field => $contents) {
      $I->fillField($field, $contents);
    }

    $I->canSeeInField('Background Color', 'Magenta');
    $I->cantSeeCheckboxIsChecked('Page Top Hero');
    $I->canSeeInField('Orientation', 'Left Image');
    $I->canSeeInField('Title Font Size', 'Large');
    $I->canSeeInField('Body Font Size', 'Large');
    $I->cantSeeCheckboxIsChecked('Author');

    $I->click('Save');
    $I->canSee($node->label(), 'h1');
    foreach ($fields as $field => $contents) {
      if (str_contains($field, '[uri]')) {
        continue;
      }
      $I->canSee($contents);
    }
  }

}