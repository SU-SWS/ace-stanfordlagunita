<?php

/**
 * @file
 * sul_profile.install
 */

use Drupal\block_content\Entity\BlockContent;

/**
 * Implements hook_removed_post_updates().
 */
function sul_profile_removed_post_updates() {
  return [
    'sul_profile_post_update_8001' => '8.x-1.13',
    'sul_profile_post_update_8003' => '8.x-1.13',
    'sul_profile_post_update_8013' => '8.x-1.13',
    'sul_profile_post_update_8014' => '8.x-2.9',
    'sul_profile_post_update_8015' => '8.x-2.9',
  ];
}

/**
 * Disable the core search module.
 */
function sul_profile_post_update_8200(){
  \Drupal::service('module_installer')->uninstall(['search']);
}

/**
 * Create the courses intro block content.
 */
function sul_profile_post_update_8201() {
  BlockContent::create([
    'uuid' => '2f343c04-f892-49bb-8d28-2c3f4653b02a',
    'type' => 'stanford_component_block',
    'info' => 'Courses Intro',
  ])->save();
}
