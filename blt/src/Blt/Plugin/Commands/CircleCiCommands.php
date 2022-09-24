<?php

namespace Gryphon\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Drupal\Core\Serialization\Yaml;

/**
 * Class GryphonCommands.
 */
class CircleCiCommands extends BltTasks {

  /**
   * Setup the directory with drupal settings files.
   *
   * @command circleci:drupal:setup
   */
  public function setupDrupal() {
    $root = $this->getConfigValue('repo.root');
    $tasks[] = $this->taskExec('dockerize -wait tcp://localhost:3306 -timeout 1m');
    $tasks[] = $this->taskExec('apachectl stop; apachectl start');

    // Cleanup any local or remnant files.
    $files = glob("$root/docroot/sites/*/local.*");
    $tasks[] = $this->taskFilesystemStack()->remove($files);
    $tasks[] = $this->taskFilesystemStack()
      ->remove("$root/docroot/core/phpunit.xml");

    $tasks[] = $this->taskFilesystemStack()->mkdir("{$_SERVER['HOME']}/.config/blt");
    $tasks[] = $this->blt()->arg('blt:telemetry:disable');
    $tasks[] = $this->blt()->arg('blt:init:setting');
    $tasks[] = $this->taskDrush()->drush('si minimal');

    return $this->collectionBuilder()->addTaskList($tasks)->run();
  }

  /**
   * Setup and and perform a fresh drupal install.
   *
   * @param array $options
   *   Keyed array of command options.
   *
   * @return \Robo\Result
   *   Command results.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   *
   * @command circleci:drupal:install
   * @options profile Specify which profile to install.
   */
  public function installDrupal(array $options = ['profile' => NULL]) {
    $this->invokeCommand('circleci:drupal:setup');

    if ($options['profile']) {
      $root = $this->getConfigValue('repo.root');
      $docroot = $this->getConfigValue('docroot');
      $ci_blt = Yaml::decode(file_get_contents("$root/blt/ci.blt.yml"));
      $ci_blt['project']['profile']['name'] = $options['profile'];
      file_put_contents("$root/blt/ci.blt.yml", Yaml::encode($ci_blt));

      if (file_exists("$docroot/profiles/custom/{$options['profile']}/config/sync")) {
        $config_sync = "\$settings['config_sync_directory'] = '$docroot/profiles/custom/{$options['profile']}/config/sync';" . PHP_EOL;
        file_put_contents("$docroot/sites/settings/ci.settings.php", $config_sync, FILE_APPEND);
      }
    }
    return $this->blt()->arg('drupal:install')->run();
  }

  /**
   * Return BLT.
   *
   * @return \Robo\Task\Base\Exec
   *   A drush exec command.
   */
  protected function blt() {
    return $this->taskExec('vendor/bin/blt')
      ->option('verbose')
      ->option('no-interaction');
  }

}
