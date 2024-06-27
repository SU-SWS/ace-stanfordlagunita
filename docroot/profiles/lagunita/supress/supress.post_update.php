<?php

/**
 * @file
 *  supress.install
 */

/**
 * Implements hook_removed_post_updates().
 */
function supress_removed_post_updates() {
  return [
    'supress_post_update_8001' => '8.x-1.13',
    'supress_post_update_8003' => '8.x-1.13',
    'supress_post_update_8013' => '8.x-1.13',
    'supress_post_update_8014' => '8.x-2.9',
    'supress_post_update_8015' => '8.x-2.9',
    'supress_post_update_8200' => '11.4.0',
    'supress_post_update_8201' => '11.4.0',
    'supress_post_update_8202' => '11.4.0',
    'supress_post_update_update_field_defs' => '11.4.0',
    'supress_post_update_samlauth' => '11.4.0',
    'supress_post_update_site_orgs' => '11.4.0',
  ];
}
