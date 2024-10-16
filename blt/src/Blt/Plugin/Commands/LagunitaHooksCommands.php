<?php

namespace Gryphon\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Common\EnvironmentDetector;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Serialization\Yaml;
use Robo\Contract\VerbosityThresholdInterface;

/**
 * Class GryphonHooksCommands for any pre or post command hooks.
 */
class LagunitaHooksCommands extends BltTasks {

  /**
   * After a multisite is created, modify the drush alias with default values.
   *
   * @hook post-command recipes:multisite:init
   */
  public function postMultiSiteInit() {
    $root = $this->getConfigValue('repo.root');
    $multisites = [];

    $default_alias = Yaml::decode(file_get_contents("$root/drush/sites/default.site.yml"));
    $sites = glob("$root/drush/sites/*.site.yml");
    foreach ($sites as $site_file) {
      $alias = Yaml::decode(file_get_contents($site_file));
      preg_match('/sites\/(.*)\.site\.yml/', $site_file, $matches);
      $site_name = $matches[1];

      $multisites[] = $site_name;
      if (count($alias) != count($default_alias)) {
        foreach ($default_alias as $environment => $env_alias) {
          $env_alias['uri'] = "$site_name.sites-pro.stanford.edu";
          $alias[$environment] = $env_alias;
        }
      }

      file_put_contents($site_file, Yaml::encode($alias));
    }

    // Add the site to the multisites in BLT's configuration.
    $root = $this->getConfigValue('repo.root');
    $blt_config = Yaml::decode(file_get_contents("$root/blt/blt.yml"));
    asort($multisites);
    $blt_config['multisites'] = array_unique($multisites);
    file_put_contents("$root/blt/blt.yml", Yaml::encode($blt_config));

    $this->say(sprintf('Remember to create the cron task. Run <info>gryphon:create-cron</info> to create the new cron job.'));
    $create_db = $this->ask('Would you like to create the database on Acquia now? (y/n)');
    if (substr(strtolower($create_db), 0, 1) == 'y') {
      $this->invokeCommand('gryphon:create-database');
    }
  }

  /**
   * Copy the default global settings for local settings.
   *
   * @hook post-command blt:init:settings
   */
  public function postInitSettings() {
    $docroot = $this->getConfigValue('docroot');
    if (!file_exists("$docroot/sites/settings/local.settings.php")) {
      $this->taskFilesystemStack()
        ->stopOnFail()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->copy("$docroot/sites/settings/default.local.settings.php", "$docroot/sites/settings/local.settings.php")
        ->run();

      $this->getConfig()
        ->expandFileProperties("$docroot/sites/settings/local.settings.php");
    }
    $keys_dir = $this->getConfigValue('repo.root') . '/keys';
    if (EnvironmentDetector::isLocalEnv() && !file_exists($keys_dir)) {
      try {
        $this->invokeCommand('sws:keys');
      }
      catch (\Exception $e) {
        // The command invocation will print the error, but don't throw the
        // error since some CI/CD tools might fail.
      }
    }
  }

  /**
   * Switch the context for the site that was copied.
   *
   * @hook pre-command artifact:ac-hooks:db-scrub
   */
  public function preDbScrub(CommandData $command_data) {
    $args_options = $command_data->getArgsAndOptions();
    // Databases should correlate directly to the site name. Except the default
    // directory which has a different database name. This allows the db scrub
    // drush command to operate on the correct database.
    $site = $args_options['db_name'] == 'sulgryphon' ? 'default' : $args_options['db_name'];
    $this->switchSiteContext($site);
  }

}
