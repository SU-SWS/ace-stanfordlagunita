<?php

/**
 * @file
 * Contains any config overrides.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;

// Decoupled sites don't need domain redirect, analytics, or search indexing at
// any time.
$config['domain_301_redirect.settings']['enabled'] = FALSE;
$settings['nobots'] = TRUE;
$config['google_analytics.settings']['account'] = '';

$config['search_api.index.algolia_search']['read_only'] = !EnvironmentDetector::isProdEnv();

if (!EnvironmentDetector::isProdEnv()) {
  /**
   * This is always set and exposed by the Acquia BLT.
   *
   * @var string $site_name
   */
  $config['stage_file_proxy.settings'] = [
    'origin' => "https://$site_name.sites-pro.stanford.edu",
    'origin_dir' => "sites/$site_name/files",
  ];
}

if (EnvironmentDetector::isAhEnv()) {
  $config['simple_oauth.settings']['public_key'] = EnvironmentDetector::getAhFilesRoot() . '/nobackup/oauth/oauth_public.key';
  $config['simple_oauth.settings']['private_key'] = EnvironmentDetector::getAhFilesRoot() . '/nobackup/oauth/oauth_private.key';
}
