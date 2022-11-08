<?php

/**
 * @file
 * Contains any config overrides.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;

if (!EnvironmentDetector::isProdEnv()) {
  $config['domain_301_redirect.settings']['enabled'] = FALSE;
}

if (EnvironmentDetector::isAhEnv()) {
  $config['simple_oauth.settings']['public_key'] = EnvironmentDetector::getAhFilesRoot() . '/nobackup/oauth/oauth.pub';
  $config['simple_oauth.settings']['private_key'] = EnvironmentDetector::getAhFilesRoot() . '/nobackup/oauth/oauth.key';
}
