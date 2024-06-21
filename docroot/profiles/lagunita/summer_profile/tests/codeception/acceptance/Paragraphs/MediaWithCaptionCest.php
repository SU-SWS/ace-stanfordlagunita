<?php

use Faker\Factory;

/**
 * Class MediaWithCaptionCest.
 *
 * @group paragraphs
 * @group media_caption
 */
class MediaWithCaptionCest {

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
   * A media with caption paragraph will display its fields.
   */
  protected function testMediaParagraph(AcceptanceTester $I) {
    $paragraph = $I->createEntity([
      'type' => 'stanford_media_caption',
      'su_media_caption_caption' => 'This is a super caption',
      'su_media_caption_link' => [
        'uri' => 'http://google.com',
        'title' => 'Google Button',
      ],
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
    $I->canSee('This is a super caption');
  }

}
