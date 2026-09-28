<?php

namespace Drupal\Tests\csp_profile\Kernel\Plugin\InstallTask;

use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\csp_profile\Plugin\InstallTask\RouteRebuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Class RouteRebuilderTest.
 */
#[Group('csp_profile')]
#[RunTestsInSeparateProcesses]
class RouteRebuilderTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'node',
    'user',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setInstallProfile('csp_profile');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->container->set('router.builder', $this->createMock(RouteBuilderInterface::class));
  }

  public function testRouteRebuild() {
    $plugin = RouteRebuilder::create($this->container, [], '', []);
    $install_state = [];
    $this->assertNull($plugin->runTask($install_state));
  }

}
