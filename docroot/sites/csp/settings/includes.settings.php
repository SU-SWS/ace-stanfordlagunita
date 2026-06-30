<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/lagunita/csp_profile/config/sync';

$next_domain = FALSE;
if (EnvironmentDetector::isDevEnv()) {
  $next_domain = 'https://continuingstudies-git-dev-sws-developers.vercel.app/';
}
elseif (EnvironmentDetector::isStageEnv()) {
  $next_domain = 'https://continuingstudies-git-test-sws-developers.vercel.app/';
}
elseif (EnvironmentDetector::isLocalEnv()) {
  $next_domain = 'http://localhost:3000';
}

if ($next_domain) {
  $config['next.next_site.prod'] = [
    'base_url' => $next_domain,
    'preview_url' => "$next_domain/api/draft",
    'revalidate_url' => "$next_domain/api/revalidate",
  ];
}

if (!EnvironmentDetector::isProdEnv()) {
  /**
   * @var string $site_name
   */
  $config['stage_file_proxy.settings'] = [
    'origin' => 'https://edit-csp.stanford.edu',
    'origin_dir' => 'sites/csp/files',
  ];
}
