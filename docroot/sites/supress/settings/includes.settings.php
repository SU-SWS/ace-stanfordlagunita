<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/lagunita/supress/config/sync';

$next_domain = FALSE;
if (EnvironmentDetector::isAhDevEnv()) {
  $next_domain = 'https://stanford-university-press-git-dev-stanford-press.vercel.app/';
}
elseif (EnvironmentDetector::isAhStageEnv()) {
  $next_domain = 'https://stanford-university-press-git-test-stanford-press.vercel.app/';
}
elseif (EnvironmentDetector::isLocalEnv()) {
  $next_domain = 'http://localhost:3000';
}

if ($next_domain) {
  $config['next.next_site.vercel'] = [
    'base_url' => $next_domain,
    'preview_url' => "$next_domain/api/draft",
    'revalidate_url' => "$next_domain/api/revalidate",
  ];
}
