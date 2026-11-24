<?php

/**
 * @file
 * Maps the dev container's hostnames to site directories.
 *
 * Included from docroot/sites/local.sites.php, which on-create.sh sets up.
 * Each site has its own port (site-ports.php), so:
 * - Web requests: Apache sets DEVCONTAINER_SITE on each port's vhost, and the
 *   request's host (whatever Docker or Codespaces forwarded) maps to it.
 * - Drush: --uri=http://localhost:<port>, or the Codespaces URL for the port.
 */

// phpcs:ignore
$devcontainer_host = function (string $http_host): string {
  // The same form Drupal's DrupalKernel::findSitePath() looks up.
  return implode('.', array_reverse(explode(':', rtrim(strtolower($http_host), '.'))));
};

if (!empty($_SERVER['DEVCONTAINER_SITE']) && !empty($_SERVER['HTTP_HOST'])) {
  $sites[$devcontainer_host($_SERVER['HTTP_HOST'])] = $_SERVER['DEVCONTAINER_SITE'];
}

foreach (require __DIR__ . '/site-ports.php' as $devcontainer_site => $devcontainer_port) {
  $sites[$devcontainer_host("localhost:$devcontainer_port")] = $devcontainer_site;
  $sites[$devcontainer_host("127.0.0.1:$devcontainer_port")] = $devcontainer_site;
  if (getenv('CODESPACE_NAME') && getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN')) {
    $sites[$devcontainer_host(getenv('CODESPACE_NAME') . "-$devcontainer_port." . getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'))] = $devcontainer_site;
  }
}
unset($devcontainer_host, $devcontainer_site, $devcontainer_port);
