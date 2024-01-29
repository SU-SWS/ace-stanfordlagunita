<?php

namespace Drupal\Tests\supress\Unit\Plugin\HelpSection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\supress\Plugin\HelpSection\ProfileConnectSection;
use Drupal\supress\Plugin\HelpSection\ProfileHelpSection;
use Drupal\Tests\UnitTestCase;

/**
 * Class ProfileConnectSectionTest
 *
 * @group stanford_profile
 * @coversDefaultClass \Drupal\supress\Plugin\HelpSection\ProfileHelpSection
 */
class ProfileHelpSectionTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());

    $container->set('link_generator', $this->createMock(LinkGeneratorInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Test the connection topics exist.
   */
  public function testHelpSections() {
    $plugin = new ProfileHelpSection([], '', []);
    $topics = $plugin->listTopics();
    $this->assertCount(6, $topics);
  }

}
