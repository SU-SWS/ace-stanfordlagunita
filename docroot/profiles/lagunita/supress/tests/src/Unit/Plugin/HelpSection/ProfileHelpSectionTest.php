<?php

namespace Drupal\Tests\supress\Unit\Plugin\HelpSection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\supress\Plugin\HelpSection\ProfileHelpSection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ProfileConnectSectionTest
 */
#[Group('supress')]
class ProfileHelpSectionTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
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
    $this->assertCount(5, $topics);
  }

}
