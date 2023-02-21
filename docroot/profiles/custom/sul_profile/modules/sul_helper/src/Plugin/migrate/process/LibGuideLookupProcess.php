<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

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

  const OAUTH_URL = 'https://lgapi-us.libapps.com/1.2/oauth/token';

  const USERS_URL = 'https://lgapi-us.libapps.com/1.2/accounts';

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    foreach ($this->getLibGuideUsers() as $user) {
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
  protected function getLibGuideUsers(): array {
    if ($cache = $this->cache->get('libguide_users')) {
      return $cache->data;
    }
    try {
      $options = [
        'json' => [
          'client_id' => Settings::get('library_libguide_client_id'),
          'client_secret' => Settings::get('library_libguide_client_secret'),
          'grant_type' => 'client_credentials',
        ],
      ];
      $response = $this->client->request('POST', self::OAUTH_URL, $options);
      $access_token = json_decode((string) $response->getBody(), TRUE);
      $token = $access_token['access_token'];
      $options = ['headers' => ['Authorization' => "Bearer $token"]];
      $response = $this->client->request('GET', self::USERS_URL, $options);
      $users = json_decode((string) $response->getBody(), TRUE);;

      $this->cache->set('libguide_users', $users);
      return $users;
    }
    catch (\Exception $e) {
    }
    return [];
  }

}
