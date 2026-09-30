<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/lagunita/sul_profile/config/sync';

$next_domain = FALSE;
if (EnvironmentDetector::isDevEnv()) {
  $next_domain = 'https://su-library-git-dev-stanford-libraries.vercel.app';
}
elseif (EnvironmentDetector::isStageEnv()) {
  $next_domain = 'https://su-library-git-test-stanford-libraries.vercel.app';
}
elseif (EnvironmentDetector::isLocalEnv()) {
  $next_domain = 'http://localhost:3000';
}

if ($next_domain) {
  $config['next.next_site.netlify'] = [
    'base_url' => $next_domain,
    'preview_url' => "$next_domain/api/draft",
    'revalidate_url' => "$next_domain/api/revalidate",
  ];
}

// Use the public search-only key: queries (the /search page, and the Algolia-backed JSON:API
// index route Bento reads) keep working, while writes are impossible.
// Set $settings['algolia_search_only_key'] in the environment's secrets.settings.php; this file
// loads after that secrets file, unlike sites/settings/config.settings.php.
if (!EnvironmentDetector::isProdEnv()) {
  $config['search_api.server.algolia_search']['backend_config']['api_key'] = $settings['algolia_search_only_key'] ?? '';
}
