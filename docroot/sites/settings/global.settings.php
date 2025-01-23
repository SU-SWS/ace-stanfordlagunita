<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

/**
 * This can be overridden for individual sites. Add or modify this value in
 * `docroot/sites/{site-name}/settings/includes.settings.php` for the respective
 * site. See related information below.
 */
$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/custom/stanford_profile/config/sync';

// When the encryption environment variable is not provided (local/ci/etc),
// fake the encryption string so that the site doesn't break.
if (!getenv('STANFORD_ENCRYPT')) {
  putenv("STANFORD_ENCRYPT=" . substr(file_get_contents("$repo_root/salt.txt"), 0, 32));
}

/**
 * An example global include file.
 *
 * To use this file, rename to global.settings.php.
 */

$settings['file_temp_path'] = '/tmp';

if (EnvironmentDetector::isAhEnv()) {
  // Set the temp directory as per https://docs.acquia.com/acquia-cloud/manage/files/broken/
  $settings['file_temp_path'] = '/mnt/gfs/' . EnvironmentDetector::getAhGroup() . '.' . EnvironmentDetector::getAhEnv() . '/tmp';
}
