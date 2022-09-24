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
class GryphonAcquiaApiCommands extends GryphonCommands {

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
    $command = sprintf('/usr/local/bin/drush9 --uri=%s --root=/var/www/html/${AH_SITE_NAME}/docroot -dv cron &>> /var/log/sites/${AH_SITE_NAME}/logs/$(hostname -s)/drush-cron-%s.log', $site, $site);
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
   * Add a new domain to the LE Cert.
   *
   * @command gryphon:add-cert-domain
   * @aliases gracd
   *
   * @param string $environment
   *   Acquia environment name: `dev`, `test`, or `prod`.
   * @param string $new_domains
   *   Comma separated list of domains.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function addDomainToCert($environment, $new_domains) {
    $domains = $this->getCurrentDomainsForEnvironment($environment);
    $main_domain = NULL;
    if (!empty($domains)) {
      $main_domain = $domains[0];
      unset($domains[0]);
    }
    $new_domains = array_filter(explode(',', $new_domains));
    $domains = array_merge($domains, $new_domains);
    asort($domains);
    array_unshift($domains, $main_domain);
    $this->issueNewCert($environment, array_filter($domains), TRUE);
  }

  /**
   * Get an array of all the current domains on the LE Cert for the environment.
   *
   * @param string $environment
   *   Environment machine name.
   *
   * @return array
   *   Array of domains.
   */
  protected function getCurrentDomainsForEnvironment($environment) {
    // Different environments have different ssh urls.
    switch ($environment) {
      case 'prod':
        $ssh_env = '';
        break;
      case 'test':
        $ssh_env = 'stg';
        break;
      default:
        $ssh_env = $environment;
    }

    // Get the list of certs on the environment. There can be multiple certs,
    // so we will have to parse the data next.
    $command = sprintf('ssh %s "~/.acme.sh/acme.sh --list --listraw"', $this->getSshUrl($environment));
    $cert_results = $this->taskExec($command)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $certs = explode("\n", trim($cert_results));

    $header = NULL;
    $cert_data = [];
    foreach ($certs as $line) {
      $line = str_getcsv($line, '|');
      if (!$header) {
        $header = $line;
        continue;
      }

      // Key the cert data by which environment the main domain is on.
      $data = array_combine($header, $line);
      preg_match('/gryphon(.*)\.prod/', $data['Main_Domain'], $cert_env);

      $cert_data[$cert_env[1] ?: 'prod'] = $data;
    }

    if (isset($cert_data[$ssh_env ?: 'prod'])) {
      $domains = explode(',', $cert_data[$ssh_env ?: 'prod']['SAN_Domains']);
      array_unshift($domains, $cert_data[$ssh_env ?: 'prod']['Main_Domain']);
      $domains = array_filter($domains, function ($domain) {
        return $domain != 'no';
      });
      return $domains;
    }
    return [];
  }

  /**
   * Ask acme.sh to create a new LE certificate.
   *
   * @command gryphon:renew-cert
   * @aliases grrc
   *
   * @option force
   *   Should the certificate be forced to renew.
   *
   * @param string $environment
   *   Acquia environment: `dev`, `test`, or `prod`.
   * @param array $options
   *   Command options.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function renewCert($environment, $options = ['force' => FALSE]) {
    $domains = $this->getCurrentDomainsForEnvironment($environment);
    $this->say(sprintf('<info>Renewing the <comment>%s</comment> certificate with the following domains:</info>', $environment));
    $this->say(implode(PHP_EOL, $domains));

    $this->invokeCommand('gryphon:enable-modules', [
      'environment' => $environment,
      'modules' => 'letsencrypt_challenge',
    ]);
    $ssh_url = $this->getSshUrl($environment);
    $command = sprintf('ssh %s "~/.acme.sh/acme.sh --renew -d %s %s"', $ssh_url, reset($domains), $options['force'] ? '--force' : '');

    // Use exec() since we need to collect the results of the command.
    // https://github.com/consolidation/Robo/issues/382#issuecomment-238364034
    exec($command, $output);
    $output = implode("\n", $output);
    $this->say($output);

    if (strpos($output, 'Skip,') === FALSE) {
      $this->invokeCommand('gryphon:update-certs', ['environment' => $environment]);
      return;
    }
    $this->say('<info>Cert is not ready to be renewed.</info>');
  }

  /**
   * Call acme.sh to issue a new cert for the environment.
   *
   * @param string $environment
   *   Acquia environment
   * @param array $domains
   *   Array of domains for the environment cert.
   * @param bool $force
   *   Force the cert to be renewed.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  protected function issueNewCert($environment, array $domains, $force = FALSE) {
    $domains = array_unique($domains);
    $domains = '-d ' . implode(' -d ', $domains);

    $this->invokeCommand('gryphon:enable-modules', [
      'environment' => $environment,
      'modules' => 'letsencrypt_challenge',
    ]);
    $ssh_url = $this->getSshUrl($environment);
    $command = sprintf('ssh %s "~/.acme.sh/acme.sh --issue %s -w /mnt/gfs/hrgryphon.%s/tmp %s --debug"', $ssh_url, $domains, $environment, $force ? '--force' : '');
    $this->taskExec($command)->run();

    $this->invokeCommand('gryphon:update-certs', ['environment' => $environment]);
  }

  /**
   * Upload and update Acquia with new cert data.
   *
   * @command gryphon:update-certs:all
   * @aliases gruca
   */
  public function updateAllCerts() {
    $this->connectAcquiaApi();
    foreach ($this->acquiaEnvironments->getAll($this->appId) as $environment) {
      // Skip RA Environment.
      if ($environment->name == 'ra') {
        continue;
      }
      $this->say(sprintf('Renewing certs for %s', $environment->name));
      $this->invokeCommand('gryphon:update-certs', ['environment' => $environment->name]);
    }
  }

  /**
   * Upload and update Acquia with the new cert data for a single environment.
   *
   * @command gryphon:update-certs
   * @aliases gruc
   *
   * @param string $environment
   *   Environment name like `dev`, `test`, or `ode123`.
   */
  public function updateCert($environment) {

    // The names of the cert are different each environment.
    switch ($environment) {
      case 'test':
        $cert_name = "hrgryphonstg.prod.acquia-sites.com";
        break;

      case 'prod':
        $cert_name = "hrgryphon.prod.acquia-sites.com";
        break;

      default:
        $cert_name = "hrgryphon$environment.prod.acquia-sites.com";
    }

    // Download the certs to local file system.
    $this->taskDeleteDir($this->getConfigValue('repo.root') . '/certs')->run();
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.$environment:/home/hrgryphon/.acme.sh/$cert_name/ @self:../certs")
      ->run();

    $local_cert_dir = $this->getConfigValue('repo.root') . '/certs';

    $cert = file_get_contents("$local_cert_dir/$cert_name.cer");
    $key = file_get_contents("$local_cert_dir/$cert_name.key");
    $intermediate = file_get_contents("$local_cert_dir/ca.cer");

    // Upload the cert information to acquia.
    $label = 'LE ' . date('Y-m-d G:i');

    $this->connectAcquiaApi();
    $environment_uuid = $this->getEnvironmentUuid($environment);
    $this->say($this->acquiaCertificates->create($environment_uuid, $label, $cert, $key, $intermediate)->message);
    sleep(3);
    $delete_cert_ids = [];
    foreach ($this->acquiaCertificates->getAll($environment_uuid) as $cert) {
      if ($cert->label == $label) {
        $this->say($this->acquiaCertificates->enable($environment_uuid, $cert->id)->message);
        continue;
      } elseif ($cert->flags->active) {
        $this->say($this->acquiaCertificates->disable($environment_uuid, $cert->id)->message);
      }

      if(strtotime($cert->expires_at) < time()){
        $delete_cert_ids[] = $cert->id;
      }
    }

    // Activate the new cert before we delete the old certs. This prevents the
    // possibility of deleting an active cert.
    foreach ($delete_cert_ids as $cert_id) {
      $this->say($this->acquiaCertificates->delete($environment_uuid, $cert_id)->message);
    }

    // Cleanup the local certs files.
    $this->taskDeleteDir($local_cert_dir)->run();
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

  /**
   * Get the url for ssh commands based on the environment.
   *
   * @param string $environment
   *   Acquia environment.
   *
   * @return string
   *   Ssh url.
   */
  protected function getSshUrl($environment) {
    // Different environments have different ssh urls.
    switch ($environment) {
      case 'prod':
        $ssh_env = '';
        break;

      case 'test':
        $ssh_env = 'stg';
        break;

      default:
        $ssh_env = $environment;
    }
    return sprintf('hrgryphon.%s@hrgryphon%s.ssh.prod.acquia-sites.com', $environment, $ssh_env);
  }

}
