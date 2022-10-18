<?php

namespace Gryphon\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;

/**
 * Class GryphonCommands.
 */
class GryphonCommands extends BltTasks {

  protected $nextjsConfig = [];

  /**
   * Generate secret keys, set the nextjs .env.local and add to Drupal.
   *
   *
   * @param string $front_dir
   *   Directory of the NextJS repo.
   * @param string|null $local_domain
   *   Local Drupal environment URL.
   * @param string $preview_domain
   *   Domain for the preview if different from localhost:3000.
   *
   * @command gryphon:connect-nextjs
   */
  public function connectNextJs(string $front_dir, string $local_domain = NULL, string $preview_domain = 'http://localhost:3000') {
    if (!file_exists("$front_dir/tailwind.config.js")) {
      throw new \Exception("$front_dir does not appear to be correct");
    }

    $front_dir = rtrim($front_dir, '/');

    $this->nextjsConfig = [
      'drupal_base_domain' => "http://$local_domain",
      'image_domain' => $local_domain,
      'preview_secret' => md5(random_int(1000, 5000)),
      'client_secret' => md5(random_int(1000, 5000)),
    ];
    if (file_exists("$front_dir/.env.local")) {
      $this->loadExistingEnvVariables("$front_dir/.env.local");
    }

    $this->taskFilesystemStack()
      ->copy("$front_dir/.env.example", "$front_dir/.env.local", TRUE)->run();

    $this->getConfig()
      ->set('NEXT_PUBLIC_DRUPAL_BASE_URL', $this->nextjsConfig['drupal_base_domain'])
      ->set('NEXT_IMAGE_DOMAIN', $this->nextjsConfig['image_domain'])
      ->set('NEXT_PUBLIC_SITE_NAME', 'Local environment')
      ->set('DRUPAL_SITE_ID', 'local')
      ->set('DRUPAL_FRONT_PAGE', '72f0069b-f1ec-4122-af73-6aa841faea90')
      ->set('DRUPAL_PREVIEW_SECRET', $this->nextjsConfig['preview_secret'])
      ->set('DRUPAL_CLIENT_ID', 'dc98f394-68ef-4e11-bab2-fd0e2931bca1')
      ->set('DRUPAL_CLIENT_SECRET', $this->nextjsConfig['client_secret'])
      ->expandFileProperties("$front_dir/.env.local");

    $this->taskDrush()
      ->drush('sul-profile:set-consumer-secret')
      ->arg('dc98f394-68ef-4e11-bab2-fd0e2931bca1')
      ->arg($this->nextjsConfig['client_secret'])
      ->run();

    $this->taskDrush()
      ->drush('sul-profile:create-nextjs-site')
      ->arg(getenv('GITPOD') ? 'Gitpod' : 'Local')
      ->arg($preview_domain ?: 'http://localhost:3000')
      ->arg($preview_domain ? $preview_domain . '/api/preview' : 'http://localhost:3000/api/preview')
      ->arg($this->nextjsConfig['preview_secret'])
      ->run();

    $repo_root = $this->getConfigValue('repo.root');
    $this->taskDrush()
      ->drush('simple-oauth:generate-keys')
      ->arg("$repo_root/keys")
      ->run();
    $copy_files = $this->taskFilesystemStack()
      ->rename("$repo_root/keys/private.key", "$repo_root/keys/oauth_private.key")
      ->rename("$repo_root/keys/public.key", "$repo_root/keys/oauth_public.key")
      ->run();

    if (!$copy_files->wasSuccessful()) {
      $this->taskFilesystemStack()
        ->remove("$repo_root/keys/private.key")
        ->remove("$repo_root/keys/public.key")
        ->run();
    }
  }

  /**
   * Load existing environment variables.
   *
   * @param string $env_file
   *   Env file.
   */
  protected function loadExistingEnvVariables(string $env_file): void {
    $file = file_get_contents($env_file);
    $lines = explode("\n", $file);
    $env_data = [];
    foreach (array_filter($lines) as $line) {
      [$key, $value] = explode('=', $line);
      $env_data[$key] = $value;
    }
    $this->nextjsConfig = [
      'drupal_base_domain' => $env_data['NEXT_PUBLIC_DRUPAL_BASE_URL'],
      'image_domain' => $env_data['NEXT_IMAGE_DOMAIN'],
      'preview_secret' => $env_data['DRUPAL_PREVIEW_SECRET'],
      'client_secret' => $env_data['DRUPAL_CLIENT_SECRET'],
    ];
  }

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
