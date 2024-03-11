<?php

namespace Gryphon\Blt\Plugin\Commands;

use AcquiaCloudApi\Endpoints\Crons;
use Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait;
use Symfony\Component\Console\Question\Question;

if (!trait_exists('Sws\BltSws\Blt\Plugin\Commands\SwsCommandTrait')) {
  return;
}

/**
 * Class GryphonAcquiaApiCommands.
 *
 * @package Gryphon\Blt\Plugin\Commands
 */
class LagunitaAcquiaApiCommands extends LagunitaCommands {

  use SwsCommandTrait;

  /**
   * Create the scheduled job within Acquia to call drush cron.
   *
   * @command gryphon:create-cron
   *
   * @param string $environment
   *   Acquia environment name: `dev`, `test`, or `prod`.
   * @param string $site
   *   Site to create the cron job.
   *
   * @throws \Exception
   */
  public function createDrushCronJob($environment, $site) {
    if (!in_array($site, $this->getConfigValue('multisites'))) {
      throw new \Exception(sprintf('%s is not one of the multisites.', $site));
    }
    $this->connectAcquiaApi();
    $site = str_replace('_', '-', str_replace('__', '.', $site));
    $command = sprintf('/usr/local/bin/cron-wrapper.sh $AH_SITE_GROUP.$AH_SITE_ENVIRONMENT https://%s.stanford.edu', $site);
    $cron_job_api = new Crons($this->acquiaApi);
    $this->say($cron_job_api->create($this->getEnvironmentUuid($environment), $command, "0 */6 * * *", "Drush Cron $site")->message);
  }

  /**
   * Create a database on the Acquia environments, should match the site name.
   *
   * @command gryphon:create-database
   * @aliases grcd
   */
  public function createDatabase() {
    $database = $this->getMachineName('What is the name of the database? This ideally will match the site directory name. No special characters please.');
    $this->connectAcquiaApi();
    $this->say($this->acquiaDatabases->create($this->appId, $database)->message);
  }

  /**
   * Add a new domain to the site and LE Cert.
   *
   * @command gryphon:add-domain
   * @aliases grad
   *
   * @param string $environment
   *   Acquia environment name: `dev`, `test`, or `prod`.
   * @param string $new_domain
   *   New stanford.edu domain.
   */
  public function addDomain($environment, $new_domain = '') {
    $this->connectAcquiaApi();
    if (empty($new_domain)) {
      $new_domain = $this->getNewDomain('What is the new url (without the protocol)?');
    }
    $this->say($this->acquiaDomains->create($this->getEnvironmentUuid($environment), $new_domain)->message);
  }

  /**
   * Ask the user for a new stanford url and validate the entry.
   *
   * @param string $message
   *   Prompt for the user.
   *
   * @return string
   *   User entered value.
   */
  protected function getNewDomain($message) {
    $question = new Question($this->formatQuestion($message));
    $question->setValidator(function ($answer) {
      if (empty($answer) || !preg_match('/\.stanford\.edu/', $answer) || preg_match('/^http/', $answer)) {
        throw new \RuntimeException(
          'You must provide a stanford.edu url. ie gryphon.stanford.edu'
        );
      }

      return $answer;
    });
    return $this->doAsk($question);
  }

  /**
   * Ask the user for a new stanford url and validate the entry.
   *
   * @param string $message
   *   Prompt for the user.
   *
   * @return string
   *   User entered value.
   */
  protected function getMachineName($message) {
    $question = new Question($this->formatQuestion($message));
    $question->setValidator(function ($answer) {
      $modified_answer = strtolower($answer);
      $modified_answer = preg_replace("/[^a-z0-9_]/", '_', $modified_answer);
      if ($modified_answer != $answer) {
        throw new \RuntimeException(
          'Only lower case alphanumeric characters with underscores are allowed.'
        );
      }
      return $answer;
    });
    return $this->doAsk($question);
  }

  /**
   * Get the environment UUID for the application from the machine name.
   *
   * @param string $name
   *   Environment machine name.
   *
   * @return string
   *   Environment UUID.
   *
   * @throws \Exception
   */
  protected function getEnvironmentUuid(string $name) {
    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $env */
    foreach ($this->acquiaEnvironments->getAll($this->appId) as $env) {
      if ($env->name == $name) {
        return $env->uuid;
      }
    }
    throw new \Exception(sprintf('Unable to find environment name %s', $name));
  }

}
