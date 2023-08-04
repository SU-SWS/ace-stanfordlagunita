<?php

/**
 * @file
 * Local development override configuration feature.
 *
 * The settings in this file will be applied to every multisites.
 */

/**
 * SimpleSaml Workgroup configuration
 */
$config['stanford_ssp.settings'] = [
  'workgroup_api_url' => 'https://workgroupsvc.stanford.edu/v1/workgroups',
  'use_workgroup_api' => TRUE,
  'workgroup_api_cert' => DRUPAL_ROOT . '/../keys/workgroup_api.cert',
  'workgroup_api_key' => DRUPAL_ROOT . '/../keys/workgroup_api.key',
];
error_reporting(E_ALL & ~E_DEPRECATED);

if (getenv('GITPOD_WORKSPACE_ID')) {
  $config['simplesamlphp_auth.settings']['activate'] = FALSE;
}

$config['next.settings']['debug'] = TRUE;

if (file_exists(DRUPAL_ROOT . '/../keys/secrets.settings.php')) {
  include DRUPAL_ROOT . '/../keys/secrets.settings.php';
}
