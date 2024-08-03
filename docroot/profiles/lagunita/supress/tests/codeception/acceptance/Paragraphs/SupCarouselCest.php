<?php

use Faker\Factory;
use Drupal\Core\File\FileExists;

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
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $name = preg_replace('/[^a-z]/', '-', strtolower($this->faker->words(3, TRUE)));
    $file_system->copy(__DIR__ . '/../assets/logo.jpg', "public://$name.jpg", FileExists::Replace);
    $file = $I->createEntity(['uri' => "public://$name.jpg"], 'file');

    $media = $I->createEntity([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ], 'media');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(3, TRUE),
    ]);
    $I->logInWithRole('contributor');
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->click('Add Carousel');

    foreach ($fields as $field => $contents) {
      $I->fillField($field, $contents);
    }
    $I->fillField('[name="su_page_banner[0][subform][sup_carousel_slides][0][subform][sup_slide_bg_image][media_library_selection]"]', $media->id());
    $I->click('Update widget', '.field--name-sup-slide-bg-image');
    $I->canSeeInField('Background Color', 'Magenta');
    $I->canSeeInField('Orientation', 'Left Image');
    $I->canSeeInField('Title Font Size', 'Large');
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
