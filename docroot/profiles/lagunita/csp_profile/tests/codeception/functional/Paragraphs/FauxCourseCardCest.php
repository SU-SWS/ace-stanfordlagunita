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
   * The approved color bar palette, by CSP design token name.
   *
   * Values are lowercase hex without a leading '#', the form the field stores.
   * Callers add the '#' where a CSS or DOM value is being built.
   */
  const PALETTE = [
    'lagunita-light' => '009ab4',
    'plum-80' => '81337a',
    'palo-verde' => '279989',
    'olive' => '8f993e',
    'cardinal-red' => '8c1515',
    'archway-light' => '766253',
  ];

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
      'instructor_one_uri' => $this->faker->url(),
      'instructor_two_name' => $this->faker->name(),
    ];

    // The second instructor only has the required Name field filled in,
    // to confirm the optional instructor fields don't block rendering.
    $instructor_one = $I->createEntity([
      'type' => 'csp_course_card_instructor',
      'csp_instructor_name' => $field_values['instructor_one_name'],
      'csp_instructor_title' => $field_values['instructor_one_title'],
      'csp_instructor_url' => [
        'uri' => $field_values['instructor_one_uri'],
        'title' => '',
        'options' => [],
      ],
    ], 'paragraph');
    $instructor_two = $I->createEntity([
      'type' => 'csp_course_card_instructor',
      'csp_instructor_name' => $field_values['instructor_two_name'],
    ], 'paragraph');

    $node = $this->createPageWithCard($I, [
      'csp_course_card_title' => $field_values['title'],
      'csp_course_card_link' => [
        'uri' => $field_values['uri'],
        'title' => $field_values['link_title'],
        'options' => [],
      ],
      'csp_course_card_format' => $field_values['format'],
      'csp_course_card_location' => $field_values['location'],
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
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSee($node->label(), 'h1');
    $I->canSee($field_values['title']);
    $I->canSeeLink($field_values['title'], $field_values['uri']);
    $I->canSee($field_values['format']);
    $I->canSee($field_values['location']);
    $I->canSee($field_values['instructor_one_name']);
    $I->canSee($field_values['instructor_one_title']);
    $I->canSee($field_values['instructor_two_name']);
    $I->canSeeNumberOfElements('.paragraph--type--csp-course-card-instructor', 2);

    // An instructor with a profile URL links their name; one without does not.
    $I->canSeeLink($field_values['instructor_one_name'], $field_values['instructor_one_uri']);
    $I->cantSeeLink($field_values['instructor_two_name']);

    // The heading holds phrasing content only - rendering the title field
    // here instead of its plain value would nest a div inside the h3.
    $I->cantSeeElement('.csp-course-card-preview__title div');
  }

  /**
   * Optional fields left empty should produce no stray DOM output.
   */
  public function testEmptyOptionalFieldsProduceNoOutput(FunctionalTester $I) {
    // The default card has no image and no instructors.
    $node = $this->createPageWithCard($I, [
      'csp_course_card_color' => ['color' => self::PALETTE['cardinal-red']],
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSee($node->label(), 'h1');
    $I->canSeeNumberOfElements('.paragraph--type--csp-course-card-instructor', 0);

    // The image is optional; its wrapper should be absent when unset.
    $I->cantSeeElement('.csp-course-card-preview__image');
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

    // The image is optional, but attach one so this covers the with-image
    // authoring path.
    $file_system = \Drupal::service('file_system');
    $uri = $file_system->copy(__DIR__ . '/logo.jpg', 'public://fcc-authoring-logo.jpg', \Drupal\Core\File\FileExists::Replace);
    $file = $I->createEntity(['uri' => $uri, 'status' => 1], 'file');
    $media = $I->createEntity([
      'bundle' => 'image',
      'name' => 'FCC Authoring Test Image',
      'field_media_image' => ['target_id' => $file->id(), 'alt' => 'Test'],
    ], 'media');

    $node = $this->createPageWithCard($I, [
      'csp_course_card_color' => ['color' => self::PALETTE['olive']],
      'csp_course_card_image' => ['target_id' => $media->id()],
    ]);

    $this->openCardEditForm($I, $node);
    $I->fillField('[name="csp_course_card_title[0][value]"]', $field_values['title']);
    $I->fillField('[name="csp_course_card_format[0][value]"]', $field_values['format']);
    $I->fillField('[name="csp_course_card_location[0][value]"]', $field_values['location']);

    // The color bar swatches live inside the collapsed "Styles" group. The
    // color_field_widget_box widget hides the text input and renders the
    // palette as buttons via JS, so wait for those to appear before clicking.
    $I->click('.ui-dialog .field-group-details summary');
    $swatch = '.ui-dialog .color_field_widget_box__square[color="#' . self::PALETTE['plum-80'] . '"]';
    $I->waitForElementVisible($swatch);
    $I->click($swatch);

    // Add an instructor block through the nested paragraphs widget.
    $I->click('Add Course Card Instructor', '.ui-dialog');
    $I->waitForElement('[name*="csp_instructor_name"]');
    $I->fillField('[name*="csp_instructor_name"]', $field_values['instructor_name']);

    $I->click('Save', '.ui-dialog-buttonpane');
    $I->waitForElementNotVisible('.ui-dialog');
    $I->click('Save', '#edit-actions');
    $I->waitForText('has been updated');

    $I->canSee($field_values['title']);
    $I->canSee($field_values['format']);
    $I->canSee($field_values['location']);
    $I->canSee($field_values['instructor_name']);

    // The swatch the editor picked should drive the preview color bar.
    $style = strtolower((string) $I->grabAttributeFrom('.csp-course-card-preview__color-bar', 'style'));
    $I->assertStringContainsString('#' . self::PALETTE['plum-80'], $style);
  }

  /**
   * Create a basic page holding one Faux Course Card.
   *
   * @param \FunctionalTester $I
   *   Tester.
   * @param array $card_fields
   *   Field values for the card, merged over the required defaults.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved node.
   */
  protected function createPageWithCard(FunctionalTester $I, array $card_fields = []) {
    $paragraph = $I->createEntity($card_fields + [
      'type' => 'csp_faux_course_card',
      'csp_course_card_title' => $this->faker->words(3, TRUE),
      'csp_course_card_link' => [
        'uri' => $this->faker->url(),
        'title' => $this->faker->words(2, TRUE),
        'options' => [],
      ],
      'csp_course_card_color' => ['color' => self::PALETTE['lagunita-light']],
    ], 'paragraph');

    return $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(4, TRUE),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);
  }

  /**
   * Open the card's edit dialog in the Layout Paragraphs editor.
   *
   * @param \FunctionalTester $I
   *   Tester.
   * @param \Drupal\node\NodeInterface $node
   *   Node holding the card.
   */
  protected function openCardEditForm(FunctionalTester $I, $node): void {
    $I->logInWithRole('site_manager');
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->scrollTo('.js-lpb-component', 0, -100);
    $I->moveMouseOver('.js-lpb-component', 10, 10);
    $I->click('Edit', '.lpb-controls');

    // Wait for the paragraph's own Title field inside the dialog.
    $I->waitForElement('.ui-dialog [name="csp_course_card_title[0][value]"]');
  }

}
