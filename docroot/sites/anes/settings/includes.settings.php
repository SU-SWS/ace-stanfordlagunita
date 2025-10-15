<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/lagunita/anes_profile/config/sync';

$next_domain = FALSE;
if (EnvironmentDetector::isDevEnv()) {
  $next_domain = 'https://anes-nextjs-git-dev-sws-developers-projects.vercel.app';
}
elseif (EnvironmentDetector::isStageEnv()) {
  $next_domain = 'https://anes-nextjs-git-test-sws-developers-projects.vercel.app';
}
elseif (EnvironmentDetector::isLocalEnv()) {
  $next_domain = 'http://localhost:3000';
}

if ($next_domain) {
  $config['next.next_site.anes'] = [
    'base_url' => $next_domain,
    'preview_url' => "$next_domain/api/draft",
    'revalidate_url' => "$next_domain/api/revalidate",
  ];
}
