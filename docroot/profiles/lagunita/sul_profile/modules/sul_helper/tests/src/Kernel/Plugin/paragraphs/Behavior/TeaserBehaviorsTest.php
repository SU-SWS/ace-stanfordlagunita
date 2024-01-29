<?php

namespace Drupal\Tests\sul_helper\Kernel\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;

class TeaserBehaviorsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'paragraphs',
    'sul_helper',
    'jsonapi',
    'serialization',
    'next',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ParagraphsType::create([
      'id' => 'stanford_entity',
      'label' => 'Card',
      'behavior_plugins' => [
        'sul_teaser_styles' => [
          'enabled' => TRUE,
        ],
      ],
    ])->save();
  }

  public function testBehaviors() {
    $paragraph = Paragraph::create(['type' => 'stanford_entity']);
    $paragraph->setBehaviorSettings('sul_teaser_styles', [
      'orientation' => 'horizontal',
      'background' => 'black',
      'background_sprinkles' => 'bottom_right',
    ]);

    /** @var \Drupal\paragraphs\ParagraphsBehaviorManager $behavior_manager */
    $behavior_manager = \Drupal::service('plugin.manager.paragraphs.behavior');
    /** @var \Drupal\paragraphs\ParagraphsBehaviorInterface $behavior_plugin */
    $behavior_plugin = $behavior_manager->createInstance('sul_teaser_styles');

    $this->assertTrue($behavior_plugin::isApplicable(ParagraphsType::load('stanford_entity')));
    $form = [];
    $form_state = new FormState();
    $element = $behavior_plugin->buildBehaviorForm($paragraph, $form, $form_state);
    $this->assertNotEmpty($element);

    $build = [];
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $behavior_plugin->view($build, $paragraph, $display, 'default');
    $this->assertContains('sul-orientation-horizontal', $build['#attributes']['class']);
    $this->assertContains('sul-background-black', $build['#attributes']['class']);
    $this->assertContains('sul-sprinkles-bottom_right', $build['#attributes']['class']);
  }

}
