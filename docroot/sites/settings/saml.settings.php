<?php

/**
 * @file
 * Simplesamlphp config settings.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;

// Don't enable SAML configs if we're on CI systems.
if (!EnvironmentDetector::isCiEnv()) {
  $env = EnvironmentDetector::getAhEnv() ?: '';
  $normalized_env = "01dev";
  $idp = 'https://idp-uat.stanford.edu/';
  $login = 'https://login-uat.stanford.edu/idp/profile/SAML2/Redirect/SSO';

  if (EnvironmentDetector::isAhStageEnv()) {
    $normalized_env = "01test";
  }
  elseif (EnvironmentDetector::isAhProdEnv()) {
    $normalized_env = "01live";
    $idp = 'https://idp.stanford.edu/';
    $login = 'https://login.stanford.edu/idp/profile/SAML2/Redirect/SSO';
  }

  $config['samlauth.authentication'] = [
    'user_name_attribute' => 'uid',
    'idp_entity_id' => $idp,
    'sp_entity_id' => "https://acquiacloudsitefactory.stanford.edu/$normalized_env",
    'idp_single_sign_on_service' => $login,
    'sp_x509_certificate' => 'file:' . EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/saml.crt',
    'sp_private_key' => 'file:' . EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/saml.pem',
    'idp_certs' => [
      'file:' . EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/signing.crt',
    ],
  ];
  $config['stanford_samlauth.settings'] = [
    'role_mapping' => [
      'workgroup_api' => [
        'cert' => EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/workgroup_api.cert',
        'key' => EnvironmentDetector::getAhFilesRoot() . '/nobackup/simplesamlphp/workgroup_api.key',
      ],
    ],
  ];
}
$config['stanford_samlauth.settings']['allowed']['groups'][99] = 'uit:sws';
