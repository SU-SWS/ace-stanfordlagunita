<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;

/**
 * Provides a libguide_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: libguide_lookup
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "libguide_lookup"
 * )
 */
class LibGuideLookupProcess extends LibLookupProcessBase {

  /**
   * OAuth API Url.
   */
  const OAUTH_URL = 'https://lgapi-us.libapps.com/1.2/oauth/token';

  /**
   * User API Url.
   */
  const USERS_URL = 'https://lgapi-us.libapps.com/1.2/accounts';

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $client, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $cache);
    $this->configuration['api'] = [
      'client_id' => Settings::get('library_libguide_client_id'),
      'client_secret' => Settings::get('library_libguide_client_secret'),
      'oauth_url' => Settings::get('library_libguide_oauth_url', self::OAUTH_URL),
      'api_endpoint' => Settings::get('library_libguide_users_url', self::USERS_URL),
    ];
  }

}
