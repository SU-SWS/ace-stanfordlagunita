<?php

namespace Drupal\Tests\supress_helper\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\supress_helper\Entity\PressAward
 */
class PressAwardTest extends KernelTestBase {

  protected static $modules = [
    'supress_helper',
    'system',
    'field',
    'user',
    'migrate'
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('sup_award');
  }

  public function testPressAwardCreation() {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('sup_award')
      ->create(['name' => 'this is an award']);
    $entity->save();
    $this->assertGreaterThan(0, $entity->id());
  }

}