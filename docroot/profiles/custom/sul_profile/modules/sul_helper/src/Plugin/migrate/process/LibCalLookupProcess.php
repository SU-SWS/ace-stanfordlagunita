<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use GuzzleHttp\ClientInterface;

/**
 * Provides a libcal_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: libcal_lookup
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "libcal_lookup"
 * )
 */
class LibCalLookupProcess extends LibLookupProcessBase {

  /**
   * OAuth API Url.
   */
  const OAUTH_URL = 'https://appointments.library.stanford.edu/1.1/oauth/token';

  /**
   * User API Url.
   */
  const USERS_URL = 'https://appointments.library.stanford.edu/1.1/appointments/users';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $client, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $cache);

    $this->configuration['api'] = [
      'client_id' => Settings::get('library_libcal_client_id'),
      'client_secret' => Settings::get('library_libcal_client_secret'),
      'oauth_url' => Settings::get('library_libcal_oauth_url', self::OAUTH_URL),
      'api_endpoint' => Settings::get('library_libcal_users_url', self::USERS_URL),
    ];
  }

}
