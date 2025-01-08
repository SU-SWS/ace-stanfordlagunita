<?php

namespace Drupal\Tests\summer_helper\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;

class SummerTest extends KernelTestBase {

  protected static $modules = ['summer_helper'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('summer_entity');
  }

  public function testEntityType() {
    $entity_type_manager = \Drupal::entityTypeManager();
    $this->assertTrue($entity_type_manager->hasDefinition('summer_entity'));
  }

}
