<?php

namespace Drupal\sul_helper;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;

/**
 * Sul service helper.
 */
class SulService implements SulServiceInterface {

  /**
   * Service constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Drupal http_client client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Drupal default cache service.
   */
  public function __construct(protected ClientInterface $client, protected CacheBackendInterface $cache) {}

  /**
   * {@inheritDoc}
   */
  public function getLibGuides(): array {
    $cache_key = 'libguides';
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }

    try {
      $token = $this->getAccessToken(Settings::get('library_libguide_client_id'), Settings::get('library_libguide_client_secret'), 'https://lgapi-us.libapps.com/1.2/oauth/token');

      $options = ['headers' => ['Authorization' => "Bearer $token"]];
      $response = $this->client->request('GET', 'https://lgapi-us.libapps.com/1.2/guides?expand=owner&status=1', $options);
      $data = json_decode((string) $response->getBody(), TRUE);

      $this->cache->set($cache_key, $data, time() + 60 * 60 * 24);
      return $data;
    }
    catch (\Exception $e) {
      // Something went wrong.
    }
    $this->cache->set($cache_key, [], time() + 60 * 60 * 24);
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getLibCalUsers(): array {
    $cache_key = 'libcal';
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }

    try {
      $token = $this->getAccessToken(Settings::get('library_libcal_client_id'), Settings::get('library_libcal_client_secret'), 'https://appointments.library.stanford.edu/1.1/oauth/token');

      $options = ['headers' => ['Authorization' => "Bearer $token"]];
      $response = $this->client->request('GET', 'https://appointments.library.stanford.edu/1.1/appointments/users', $options);
      $data = json_decode((string) $response->getBody(), TRUE);

      $this->cache->set($cache_key, $data, time() + 60 * 60 * 24);
      return $data;
    }
    catch (\Exception $e) {
      // Something went wrong.
    }
    $this->cache->set($cache_key, [], time() + 60 * 60 * 24);
    return [];
  }

  /**
   * Get the oauth bearer token.
   *
   * @param string|int $client_id
   *   API Client ID for OAuth.
   * @param string $client_secret
   *   API Client Secret for OAuth.
   * @param string $oauth_url
   *   OAuth API Url.
   *
   * @return string
   *   OAuth Bearer token.
   */
  protected function getAccessToken(string|int $client_id, string $client_secret, string $oauth_url): string {
    $cache_key = 'lib:' . substr(md5($oauth_url), 0, 10);
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }

    $options = [
      'json' => [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'client_credentials',
      ],
    ];
    $response = $this->client->request('POST', $oauth_url, $options);
    $access_token = json_decode((string) $response->getBody(), TRUE);

    // Cache just enough for this execution.
    $this->cache->set($cache_key, $access_token['access_token'], time() + 30);
    return $access_token['access_token'];
  }

}
