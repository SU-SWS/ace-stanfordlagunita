<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Configuration file for multi-site support and directory aliasing feature.
 *
 * This file is required for multi-site support and also allows you to define a
 * set of aliases that map hostnames, ports, and pathnames to configuration
 * directories in the sites directory. These aliases are loaded prior to
 * scanning for directories, and they are exempt from the normal discovery
 * rules. See default.settings.php to view how Drupal discovers the
 * configuration directory when no alias is found.
 *
 * Aliases are useful on development servers, where the domain name may not be
 * the same as the domain of the live server. Since Drupal stores file paths in
 * the database (files, system table, etc.) this will ensure the paths are
 * correct when the site is deployed to a live server.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/sites.php'.
 *
 * Aliases are defined in an associative array named $sites. The array is
 * written in the format: '<port>.<domain>.<path>' => 'directory'. As an
 * example, to map https://www.drupal.org:8080/mysite/test to the configuration
 * directory sites/example.com, the array should be defined as:
 * @code
 * $sites = [
 *   '8080.www.drupal.org.mysite.test' => 'example.com',
 * ];
 * @endcode
 * The URL, https://www.drupal.org:8080/mysite/test/, could be a symbolic link
 * or an Apache Alias directive that points to the Drupal root containing
 * index.php. An alias could also be created for a subdomain. See the
 * @link https://www.drupal.org/documentation/install online Drupal
 *   installation guide @endlink for more information on setting up domains,
 *   subdomains, and subdirectories.
 *
 * The following examples look for a site configuration in sites/example.com:
 * @code
 * URL: http://dev.drupal.org
 * $sites['dev.drupal.org'] = 'example.com';
 *
 * URL: http://localhost/example
 * $sites['localhost.example'] = 'example.com';
 *
 * URL: http://localhost:8080/example
 * $sites['8080.localhost.example'] = 'example.com';
 *
 * URL: https://www.drupal.org:8080/mysite/test/
 * $sites['8080.www.drupal.org.mysite.test'] = 'example.com';
 * @endcode
 *
 * @see default.settings.php
 * @see \Drupal\Core\DrupalKernel::getSitePath()
 * @see https://www.drupal.org/documentation/install/multi-site
 */

$sites_settings = glob(__DIR__ . '/*/settings.php');
foreach ($sites_settings as $site_setting) {
  $site_setting = str_replace(__DIR__ . '/', '', $site_setting);
  $site_name = substr($site_setting, 0, strpos($site_setting, '/'));
  if ($site_name == 'default') {
    continue;
  }

  $sites["$site_name-dev.sites-pro.stanford.edu"] = $site_name;
  $sites["$site_name-dev.stanford.edu"] = $site_name;
  $sites["dev-$site_name.stanford.edu"] = $site_name;
  $sites["edit-dev-$site_name.stanford.edu"] = $site_name;

  $sites["$site_name-test.sites-pro.stanford.edu"] = $site_name;
  $sites["$site_name-test.stanford.edu"] = $site_name;
  $sites["test-$site_name.stanford.edu"] = $site_name;
  $sites["edit-test-$site_name.stanford.edu"] = $site_name;

  $sites["$site_name.sites-pro.stanford.edu"] = $site_name;
  $sites["$site_name.stanford.edu"] = $site_name;
  $sites["edit-$site_name.stanford.edu"] = $site_name;

  $sites[$site_name] = $site_name;
}

$sites['sup.org'] = 'supress';

if (file_exists(__DIR__ . '/local.sites.php')) {
  require __DIR__ . '/local.sites.php';
}
