<?php

namespace Drupal\Tests\supress_helper\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\supress_helper\Entity\PressAward
 */
class PressEntityTest extends KernelTestBase {

  protected static $modules = [
    'supress_helper',
    'system',
    'field',
    'user',
    'migrate',
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
