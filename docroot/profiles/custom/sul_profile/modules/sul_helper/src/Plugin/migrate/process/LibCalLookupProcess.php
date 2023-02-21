<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

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

  const OAUTH_URL = 'https://appointments.library.stanford.edu/1.1/oauth/token';

  const USERS_URL = 'https://appointments.library.stanford.edu/1.1/appointments/users';

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    foreach ($this->getLibCalUsers() as $user) {
      if ($value == $user['email']) {
        return $user['id'];
      }
    }
    return NULL;
  }

  /**
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getLibCalUsers(): array {
    if ($cache = $this->cache->get('libcal_users')) {
      return $cache->data;
    }
    try {
      $options = [
        'json' => [
          'client_id' => Settings::get('library_libcal_client_id'),
          'client_secret' => Settings::get('library_libcal_client_secret'),
          'grant_type' => 'client_credentials',
        ],
      ];
      $response = $this->client->request('POST', self::OAUTH_URL, $options);
      $access_token = json_decode((string) $response->getBody(), TRUE);
      $token = $access_token['access_token'];
      $options = ['headers' => ['Authorization' => "Bearer $token"]];
      $response = $this->client->request('GET', self::USERS_URL, $options);
      $users = json_decode((string) $response->getBody(), TRUE);;

      $this->cache->set('libcal_users', $users);
      return $users;
    }
    catch (\Exception $e) {
    }
    return [];
  }

}
