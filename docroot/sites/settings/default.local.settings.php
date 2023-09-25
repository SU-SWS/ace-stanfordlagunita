<?php

/**
 * @file
 * Local development override configuration feature.
 *
 * The settings in this file will be applied to every multisites.
 */

error_reporting(E_ALL & ~E_DEPRECATED);

/**
 * SAML configuration
 */
$config['samlauth.authentication']['sp_x509_certificate'] = 'file:' . DRUPAL_ROOT . '/../keys/saml.crt';
$config['samlauth.authentication']['sp_private_key'] = 'file:' . DRUPAL_ROOT . '/../keys/saml.pem';
$config['samlauth.authentication']['idp_certs'] = [
  'file:' . DRUPAL_ROOT . '/../keys/signing.crt',
];
$config['stanford_samlauth.settings']['role_mapping']['workgroup_api'] = [
  'cert' => DRUPAL_ROOT . '/../keys/workgroup_api.cert',
  'key' => DRUPAL_ROOT . '/../keys/workgroup_api.key',
];

$config['next.settings']['debug'] = TRUE;

if (file_exists(DRUPAL_ROOT . '/../keys/secrets.settings.php')) {
  include DRUPAL_ROOT . '/../keys/secrets.settings.php';
}

// Saml login doesn't work on gitpod or tugboat, don't set config values.
if (getenv('GITPOD_WORKSPACE_URL') || getenv('TUGBOAT_REPO')) {
  unset($config['samlauth.authentication']);

  $config['stanford_samlauth.settings'] = [
    'hide_local_login' => FALSE,
    'local_login_fieldset_open' => true,
  ];
}
