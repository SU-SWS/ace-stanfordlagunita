<?php

/**
 * @file
 * supress.profile
 */

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Installer\InstallerKernel;

/**
 * Implements hook_install_tasks().
 */
function supress_install_tasks(&$install_state) {
  return ['supress_final_task' => []];
}

/**
 * Perform final tasks after the profile has completed installing.
 *
 * @param array $install_state
 *   Current install state.
 */
function supress_final_task(array &$install_state) {
  \Drupal::service('plugin.manager.install_tasks')->runTasks($install_state);
}

/**
 * Implements hook_ENTITY_TYPE_presave().
 */
function supress_config_pages_presave(ConfigPagesInterface $config_page) {
  // During install, rebuild the router when saving a config page. This prevents
  // an error if the config page route doesn't exist for it yet. Event
  // subscriber doesn't work for this since it's during installation.
  if (InstallerKernel::installationAttempted()) {
    \Drupal::service('router.builder')->rebuild();
  }
}
