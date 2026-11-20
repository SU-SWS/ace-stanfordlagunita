<?php

namespace Drupal\Tests\supress_helper\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\supress_helper\Entity\PressEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the PressEntity class.
 */
#[Group('supress_helper')]
#[CoversClass(PressEntity::class)]
#[RunTestsInSeparateProcesses]
class PressEntityTest extends KernelTestBase {

  protected static $modules = [
    'supress_helper',
    'system',
    'field',
    'user',
    'migrate',
    'config_pages',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('press_type');
    $this->installEntitySchema('press');

    \Drupal::entityTypeManager()
      ->getStorage('press_type')
      ->create(['id' => 'price', 'label' => 'price'])
      ->save();
  }

  public function testPressAwardCreation() {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('press')
      ->create(['title' => 'this is an award', 'bundle' => 'price']);
    $entity->save();
    $this->assertGreaterThan(0, $entity->id());
  }

}
