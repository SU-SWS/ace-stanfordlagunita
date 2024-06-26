<?php

/**
 * @file
 *  sul_profile.install
 */

/**
 * Implements hook_removed_post_updates().
 */
function  sul_profile_removed_post_updates() {
  return [
    ' sul_profile_post_update_8001' => '8.x-1.13',
    ' sul_profile_post_update_8003' => '8.x-1.13',
    ' sul_profile_post_update_8013' => '8.x-1.13',
    ' sul_profile_post_update_8014' => '8.x-2.9',
    ' sul_profile_post_update_8015' => '8.x-2.9',
    ' sul_profile_post_update_8200' => '11.4.0',
    ' sul_profile_post_update_8201' => '11.4.0',
    ' sul_profile_post_update_8202' => '11.4.0',
    ' sul_profile_post_update_update_field_defs' => '11.4.0',
    ' sul_profile_post_update_samlauth' => '11.4.0',
    ' sul_profile_post_update_site_orgs' => '11.4.0',
  ];
}
