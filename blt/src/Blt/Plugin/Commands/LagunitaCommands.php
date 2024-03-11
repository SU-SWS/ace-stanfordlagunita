<?php

namespace Gryphon\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;

/**
 * Class GryphonCommands.
 */
class LagunitaCommands extends BltTasks {

  /**
   * Enable a list of modules for all sites on a given environment.
   *
   * @param string $environment
   *   Environment name like `dev`, `test`, or `ode123`.
   * @param string $modules
   *   Comma separated list of modules to enable.
   *
   * @example blt gryphon:enable-modules dev views_ui,field
   *
   * @command gryphon:enable-modules
   * @aliases grem
   *
   */
  public function enableModules($environment, $modules) {
    $commands = $this->collectionBuilder();
    foreach ($this->getConfigValue('multisites') as $site) {
      $commands->addTask($this->taskDrush()
        ->alias("$site.$environment")
        ->drush('en')
        ->arg($modules));
    }

    $commands->run();
  }

}
