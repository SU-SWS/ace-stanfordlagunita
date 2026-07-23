<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Faux Course Card paragraph tests.
 */
#[CodeceptionAttribute\Group('paragraphs')]
#[CodeceptionAttribute\Group('faux-course-card')]
class FauxCourseCardCest {

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
   * All card-level and instructor field values should display.
   */
  public function testFauxCourseCardFields(FunctionalTester $I) {
    $field_values = [
      'title' => $this->faker->words(3, TRUE),
      'uri' => $this->faker->url(),
      'link_title' => $this->faker->words(3, TRUE),
      'format' => 'On Campus',
      'location' => 'Stanford, CA',
      'instructor_one_name' => $this->faker->name(),
      'instructor_one_title' => $this->faker->jobTitle(),
      'instructor_two_name' => $this->faker->name(),
    ];

    // The second instructor only has the required Name field filled in,
    // to confirm the optional instructor fields don't block rendering.
    $instructor_one = $I->createEntity([
      'type' => 'csp_course_card_instructor',
      'csp_instructor_name' => $field_values['instructor_one_name'],
      'csp_instructor_title' => $field_values['instructor_one_title'],
    ], 'paragraph');
    $instructor_two = $I->createEntity([
      'type' => 'csp_course_card_instructor',
      'csp_instructor_name' => $field_values['instructor_two_name'],
    ], 'paragraph');

    $paragraph = $I->createEntity([
      'type' => 'csp_faux_course_card',
      'csp_course_card_title' => $field_values['title'],
      'csp_course_card_link' => [
        'uri' => $field_values['uri'],
        'title' => $field_values['link_title'],
        'options' => [],
      ],
      'csp_course_card_format' => $field_values['format'],
      'csp_course_card_location' => $field_values['location'],
      'csp_course_card_color' => 'lagunita',
      'csp_course_card_instructors' => [
        [
          'target_id' => $instructor_one->id(),
          'entity' => $instructor_one,
        ],
        [
          'target_id' => $instructor_two->id(),
          'entity' => $instructor_two,
        ],
      ],
    ], 'paragraph');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(4, TRUE),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSee($node->label(), 'h1');
    $I->canSee($field_values['title']);
    $I->canSeeLink($field_values['link_title'], $field_values['uri']);
    $I->canSee($field_values['format']);
    $I->canSee($field_values['location']);
    $I->canSee('Lagunita');
    $I->canSee($field_values['instructor_one_name']);
    $I->canSee($field_values['instructor_one_title']);
    $I->canSee($field_values['instructor_two_name']);
    $I->canSeeNumberOfElements('.paragraph--type--csp-course-card-instructor', 2);
  }

  /**
   * An instructor block that was never added should produce no DOM output.
   */
  public function testEmptyInstructorBlockProducesNoOutput(FunctionalTester $I) {
    $paragraph = $I->createEntity([
      'type' => 'csp_faux_course_card',
      'csp_course_card_title' => $this->faker->words(3, TRUE),
      'csp_course_card_link' => [
        'uri' => $this->faker->url(),
        'title' => $this->faker->words(2, TRUE),
        'options' => [],
      ],
      'csp_course_card_color' => 'cardinal',
    ], 'paragraph');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(4, TRUE),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSee($node->label(), 'h1');
    $I->canSeeNumberOfElements('.paragraph--type--csp-course-card-instructor', 0);
  }

  /**
   * Editors should be able to author all fields through the editing form.
   */
  public function testFauxCourseCardAuthoringForm(FunctionalTester $I) {
    $field_values = [
      'title' => $this->faker->words(3, TRUE),
      'format' => 'Online',
      'location' => 'Virtual',
      'instructor_name' => $this->faker->name(),
    ];

    $paragraph = $I->createEntity([
      'type' => 'csp_faux_course_card',
      'csp_course_card_title' => $this->faker->words(3, TRUE),
      'csp_course_card_link' => [
        'uri' => $this->faker->url(),
        'title' => $this->faker->words(2, TRUE),
        'options' => [],
      ],
      'csp_course_card_color' => 'olive',
    ], 'paragraph');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(4, TRUE),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);

    $I->logInWithRole('site_manager');
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->scrollTo('.js-lpb-component', 0, -100);
    $I->moveMouseOver('.js-lpb-component', 10, 10);
    $I->click('Edit', '.lpb-controls');

    $I->waitForText('Title');
    $I->fillField('Title', $field_values['title']);
    $I->fillField('Format', $field_values['format']);
    $I->fillField('Location', $field_values['location']);

    // The color bar select lives inside the collapsed "Styles" group.
    $I->click('Styles');
    $I->selectOption('Color bar', 'Plum');

    // Add an instructor block through the nested paragraphs widget.
    $I->click('Add Instructor');
    $I->waitForText('Name');
    $I->fillField('Name', $field_values['instructor_name']);

    $I->click('Save', '.ui-dialog-buttonpane');
    $I->waitForElementNotVisible('.ui-dialog');
    $I->click('Save', '#edit-actions');

    $I->canSee($field_values['title']);
    $I->canSee($field_values['format']);
    $I->canSee($field_values['location']);
    $I->canSee('Plum');
    $I->canSee($field_values['instructor_name']);
  }

}
