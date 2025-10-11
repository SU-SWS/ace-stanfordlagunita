<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

/**
 * Class AccordionCest.
 */
#[CodeceptionAttribute\Group('paragraphs')]
#[CodeceptionAttribute\Group('accordions')]
class AccordionCest {

  protected $faker;

  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Create and check the accordion.
   */
  public function testCreatingAccordion(FunctionalTester $I) {
    $q_and_a = [
      [$this->faker->words(3, TRUE), $this->faker->paragraph()],
      [$this->faker->words(3, TRUE), $this->faker->paragraph()],
      [$this->faker->words(3, TRUE), $this->faker->paragraph()],
    ];

    $questions = [];
    foreach ($q_and_a as $item) {
      $question_paragraph = $I->createEntity([
        'type' => 'stanford_accordion',
        'su_accordion_title' => $item[0],
        'su_accordion_body' => [
          'value' => $item[1],
          'format' => 'stanford_minimal_html',
        ],
      ], 'paragraph');
      $questions[] = [
        'target_id' => $question_paragraph->id(),
        'entity' => $question_paragraph,
      ];
    }

    $paragraph = $I->createEntity([
      'type' => 'stanford_faq',
      'su_faq_headline' => $this->faker->words(4, TRUE),
      'su_faq_description' => [
        'value' => $this->faker->paragraph(),
        'format' => 'stanford_html',
      ],
      'su_faq_questions' => $questions,
    ], 'paragraph');

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->text(30),
      'su_page_components' => [
        'target_id' => $paragraph->id(),
        'entity' => $paragraph,
      ],
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSee($node->label(), 'h1');

    foreach ($q_and_a as $delta => $item) {
      [$question, $answer] = $item;
      $I->canSee($question);
      $I->cantSee($answer);

      $child_index = $delta + 1;
      $I->click($question);
      $I->waitForText($answer);
      $I->click($question);
    }

    $I->click('Expand All');
    foreach ($q_and_a as $item) {
      $I->canSee($item[1]);
    }

    $I->click('Collapse All');
    foreach ($q_and_a as $item) {
      $I->cantSee($item[1]);
    }
  }

  #[CodeceptionAttribute\Group('accordion-wysiwyg')]
  public function testAccordionParagraphRichText(FunctionalTester $I) {
    $layout = $I->createEntity(['type' => 'stanford_layout'], 'paragraph');
    $layout->setBehaviorSettings('layout_paragraphs', [
      'layout' => 'layout_paragraphs_1_column',
    ]);
    $layout->save();

    $headline = $this->faker->word(2);
    $description = $this->faker->sentence(3);
    
    // Create accordion items with rich text content
    $accordion_items = [];
    $rich_text_samples = [
      '<h2>Heading 2</h2><p>' . $this->faker->paragraph() . '</p>',
      '<h3>Heading 3</h3><ul><li>Item 1</li><li>Item 2</li></ul>',
      '<p><strong>Bold</strong> text</p>',
    ];
    
    for ($i = 0; $i < 3; $i++) {
      $accordion_items[] = $I->createEntity([
        'type' => 'stanford_accordion',
        'su_accordion_title' => "Title " . ($i + 1),
        'su_accordion_body' => [
          'value' => $rich_text_samples[$i],
          'format' => 'stanford_html',
        ],
      ], 'paragraph');
    }

    // Create the accordion paragraph with entity references
    $paragraph = $I->createEntity([
      'type' => 'stanford_faq',
      'su_faq_headline' => $headline,
      'su_faq_description' => $description,
      'su_faq_questions' => array_map(function($item) {
        return [
          'target_id' => $item->id(),
          'entity' => $item,
        ];
      }, $accordion_items),
    ], 'paragraph');
    $paragraph->save();

    // Create the page node
    $node = $I->createEntity([
      'title' => $this->faker->words(3, TRUE),
      'type' => 'stanford_page',
      'su_page_components' => [
        ['target_id' => $layout->id(), 'entity' => $layout],
        ['target_id' => $paragraph->id(), 'entity' => $paragraph],
      ],
    ], 'node');
    
    $I->logInWithRole('site_manager');
    
    $I->amOnPage($node->toUrl('edit-form')->toString());
    
    $I->seeElement('form.node-stanford-page-edit-form');
    $I->seeElement('h2', ['text' => 'Heading 2']);
    $I->see('Paragraph');
    $I->seeElement('ul li', ['text' => 'Item 1']);
    $I->seeElement('ul li', ['text' => 'Item 2']);
  }
}
