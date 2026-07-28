<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Tests the CSP "Section width" option on multi-column layout sections.
 */
#[CodeceptionAttribute\Group('paragraphs')]
#[CodeceptionAttribute\Group('section-width')]
class SectionWidthCest {

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
   * The "Section width" option defaults to Full and persists once changed.
   */
  public function testSectionWidthOptionPersists(FunctionalTester $I) {
    $node = $this->createNodeWithSection($I, 'layout_paragraphs_2_column');

    $I->logInWithRole('contributor');
    $I->amOnPage($node->toUrl('edit-form')->toString());

    // Open the section's layout configuration dialog.
    $this->openLayoutSettings($I);

    // The select is present and defaults to "Full".
    $I->waitForText('Section width');
    $I->seeOptionIsSelected('Section width', 'Full (1500px)');

    // Choose a narrower width and save the dialog.
    $I->selectOption('Section width', 'Wide (1300px)');
    $I->seeOptionIsSelected('Section width', 'Wide (1300px)');
    $I->click('Save', '.ui-dialog-buttonpane');
    $I->waitForElementNotVisible('.ui-dialog');

    // Save the node.
    $I->click('Save', '#edit-actions');
    $I->canSee($node->label(), 'h1');

    // Re-open the section and confirm the chosen width was persisted.
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $this->openLayoutSettings($I);
    $I->waitForText('Section width');
    $I->seeOptionIsSelected('Section width', 'Wide (1300px)');
  }

  /**
   * The "Section width" option is also available on the 3-column layout.
   */
  public function testSectionWidthOnThreeColumn(FunctionalTester $I) {
    $node = $this->createNodeWithSection($I, 'layout_paragraphs_3_column');

    $I->logInWithRole('contributor');
    $I->amOnPage($node->toUrl('edit-form')->toString());

    $this->openLayoutSettings($I);
    $I->waitForText('Section width');
    $I->seeOptionIsSelected('Section width', 'Full (1500px)');
  }

  /**
   * The "Section width" option is not offered on single-column sections.
   */
  public function testSectionWidthNotOnOneColumn(FunctionalTester $I) {
    $node = $this->createNodeWithSection($I, 'layout_paragraphs_1_column');

    $I->logInWithRole('contributor');
    $I->amOnPage($node->toUrl('edit-form')->toString());

    $this->openLayoutSettings($I);
    // The dialog is open (the "Space below section" option is always present);
    // the "Section width" option is intentionally excluded here.
    $I->waitForText('Space below section');
    $I->dontSee('Section width');
  }

  /**
   * Creates a page node with a single layout section containing a component.
   *
   * @param \FunctionalTester $I
   *   The tester.
   * @param string $layout
   *   The layout plugin id, e.g. "layout_paragraphs_2_column".
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   */
  protected function createNodeWithSection(FunctionalTester $I, string $layout) {
    // The region the component lives in differs by layout: single-column
    // sections use "main", multi-column sections use "left".
    $region = $layout === 'layout_paragraphs_1_column' ? 'main' : 'left';

    /** @var \Drupal\paragraphs\ParagraphInterface $section */
    $section = $I->createEntity(['type' => 'stanford_layout'], 'paragraph');
    $section->setBehaviorSettings('layout_paragraphs', [
      'layout' => $layout,
      'config' => [
        'bg_color' => '',
        'bottom_margin' => NULL,
        'bottom_padding' => NULL,
        'top_padding' => NULL,
      ],
    ]);
    $section->save();

    /** @var \Drupal\paragraphs\ParagraphInterface $wysiwyg */
    $wysiwyg = $I->createEntity([
      'type' => 'stanford_wysiwyg',
      'su_wysiwyg_text' => [
        'value' => $this->faker->sentence(),
        'format' => 'stanford_html',
      ],
    ], 'paragraph');
    $wysiwyg->setBehaviorSettings('layout_paragraphs', [
      'parent_uuid' => $section->uuid(),
      'region' => $region,
    ]);
    $wysiwyg->save();

    return $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(3, TRUE),
      'su_page_components' => [
        ['target_id' => $section->id(), 'entity' => $section],
        ['target_id' => $wysiwyg->id(), 'entity' => $wysiwyg],
      ],
    ]);
  }

  /**
   * Opens the layout configuration dialog for the section in the builder.
   *
   * @param \FunctionalTester $I
   *   The tester.
   */
  protected function openLayoutSettings(FunctionalTester $I) {
    $I->waitForElementVisible('.js-lpb-component');
    $I->moveMouseOver('.js-lpb-component', 10, 10);
    $I->click('.lpb-layout a.lpb-edit');
  }

}
