<?php

use Codeception\Attribute as CodeceptionAttribute;
use Faker\Factory;

#[CodeceptionAttribute\Group('paragraphs')]
class StanfordAccordionCest {

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

  #[CodeceptionAttribute\Group('accordion-wysiwyg')]
  public function testAccordionParagraph(AcceptanceTester $I) {
    $layout = $I->createEntity(['type' => 'stanford_layout'], 'paragraph');
    $layout->setBehaviorSettings('layout_paragraphs', [
      'layout' => 'layout_paragraphs_1_column',
    ]);
    $layout->save();

    $headline = $this->faker->sentence(3);
    $description = $this->faker->sentence(3);
    $accordion_items = [];
    for ($i = 0; $i < 3; $i++) {
      $accordion_items[] = $I->createEntity([
        'type' => 'stanford_accordion',
        'su_accordion_title' => $this->faker->sentence(4),
        'su_accordion_body' => [
          'value' => $this->faker->paragraphs(2, true),
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
        ['target_id' => $paragraph ->id(), 'entity' => $paragraph],
      ],
    ], 'node');
    
    $I->amOnPage($node->toUrl()->toString());
    $I->canSee($node->label(), 'h1');
    
    $I->canSee($headline);
    $I->canSee($description);

    
    foreach ($accordion_items as $accordion_item) {
      $accordion_title = $accordion_item->get('su_accordion_title')->value;
      $I->canSee($accordion_title);
      $body_text = $accordion_item->get('su_accordion_body')->value;
      if ($body_text) {
        $plain_text = strip_tags($body_text);
        $I->canSee($plain_text);
      }
    }
  }
}