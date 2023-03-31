<?php

use Faker\Factory;

/**
 * Study Place page tests.
 *
 * @group sul-study-place
 */
class StudyPlaceCest {

  /**
   * Faker generator.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  public function __construct() {
    $this->faker = Factory::create();
  }

  public function testStudyPageCreation(AcceptanceTester $I) {
    $branch = $I->createEntity([
      'type' => 'sul_library',
      'title' => $this->faker->words(3, TRUE),
    ]);
    $place_type = $I->createEntity([
      'vid' => 'sul_study_place_type',
      'name' => $this->faker->word,
    ], 'taxonomy_term');
    $feature = $I->createEntity([
      'vid' => 'sul_study_place_features',
      'name' => $this->faker->word,
    ], 'taxonomy_term');
    $capacity = $I->createEntity([
      'vid' => 'study_place_capacity',
      'name' => $this->faker->word,
    ], 'taxonomy_term');

    $title = $this->faker->words(3, TRUE);
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/sul_study_place');
    $I->fillField('Title', $title);


    $I->selectOption('Type of Place', $place_type->label());
    $I->selectOption('Study Features', $feature->label());
    $I->selectOption('Capacity', $capacity->label());
    $I->selectOption('Branch Location', $branch->label());
    $I->fillField('LibCal Id Number', $this->faker->numberBetween());

    $I->click('Save');
    $I->canSee($title, 'h1');
  }

}
