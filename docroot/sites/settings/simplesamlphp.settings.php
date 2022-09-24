<?php

/**
 * @file
 * Simplesamlphp config settings.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;

$config['stanford_ssp.settings'] = [
  'workgroup_api_url' => 'https://workgroupsvc.stanford.edu/v1/workgroups',
  'use_workgroup_api' => TRUE,
  'workgroup_api_cert' => EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/workgroup_api.cert',
  'workgroup_api_key' => EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/workgroup_api.key',
];

// Always show local login when not on Acquia.
if (!EnvironmentDetector::isAhEnv()) {
  $config['stanford_ssp.settings']['hide_local_login'] = FALSE;
}

$config['simplesamlphp_auth.settings'] = [
  'langcode' => 'en',
  'default_langcode' => 'en',
  'activate' => TRUE,
  'mail_attr' => 'mail',
  'unique_id' => 'uid',
  'user_name' => 'uid',
  'auth_source' => 'default-sp',
  'login_link_display_name' => 'Stanford Login',
  'header_no_cache' => TRUE,
  'user_register_original' => 'visitors',
  'register_users' => TRUE,
  'autoenablesaml' => TRUE,
  'debug' => FALSE,
  'secure' => TRUE,
  'httponly' => TRUE,
  'role' => [
    'eval_every_time' => 2,
  ],
  'allow' => [
    'set_drupal_pwd' => FALSE,
    'default_login' => TRUE,
  ],
  'sync' => [
    'mail' => TRUE,
    'user_name' => TRUE,
  ],
];
