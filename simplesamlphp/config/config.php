<?php

/**
 * @file
 * The configuration of SimpleSAMLphp.
 */

// Common and default configuration items for all simplesamlphp setups.
use Acquia\Blt\Robo\Common\EnvironmentDetector;

require dirname(__FILE__) . '/common.config.php';

// Set some security and other configs that are set above, however we
// overwrite them here to keep all changes in one area.
$config['technicalcontact_name'] = "Stanford Web Services";
$config['technicalcontact_email'] = "sws-developers@lists.stanford.edu";

$config['authproc.sp'] = [
  10 => [
    'class' => 'core:AttributeMap',
    'removeurnprefix',
    'oid2name',
  ],
  90 => 'core:LanguageAdaptor',
];

// Prevent Varnish from interfering with SimpleSAMLphp.
// SSL terminated at the ELB/balancer so we correctly set the SERVER_PORT
// and HTTPS for SimpleSAMLphp baseurl configuration.
$protocol = 'http://';
$port = ':80';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $_SERVER['SERVER_PORT'] = 443;
  $_SERVER['HTTPS'] = 'true';
  $protocol = 'https://';
  $port = ':' . $_SERVER['SERVER_PORT'];
}

// Support multi-site and single site installations at different base URLs.
if (isset($_SERVER['HTTP_HOST'])) {
  $config['baseurlpath'] = $protocol . $_SERVER['HTTP_HOST'] . $port . '/simplesaml/';
}

// Configuration specific to Acquia's environments.
if (EnvironmentDetector::isAhEnv()) {
  include __DIR__ . '/acquia.config.php';
}
else {
  
  // Local environment configs.
  if (!getenv('GITPOD_WORKSPACE_ID') && file_exists(__DIR__ . "/local.config.php")) {
    include __DIR__ . "/local.config.php";
  }
}
