<?php

namespace Drupal\sul_helper\Plugin\migrate\process;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 *
 * @DCG
 * ContainerFactoryPluginInterface is optional here. If you have no need for
 * external services just remove it and all other stuff except transform()
 * method.
 */
abstract class LibLookupProcessBase extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a LibCalLookupProcess plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $client, CacheBackendInterface $cache) {
    $this->configuration['api'] = [

    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $users = $this->getApiData(...$this->configuration['api']);
    foreach ($users as $user) {
      if ($value == $user['email']) {
        return $user['id'];
      }
    }
    return NULL;
  }


  /**
   * Call the API and return the data.
   *
   * @param string|int $client_id
   *   API Client ID for OAuth.
   * @param string $client_secret
   *   API Client Secret for OAuth.
   * @param string $oauth_url
   *   OAuth API Url.
   * @param string $api_endpoint
   *   API Endpoint.
   *
   * @return array
   *   Return data from the API.
   */
  protected function getApiData(string|int $client_id, string $client_secret, string $oauth_url, string $api_endpoint): array {
    $cache_key = 'lib:' . substr(md5($api_endpoint . $oauth_url), 0, 10);
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }
    try {
      $token = $this->getAccessToken($client_id, $client_secret, $oauth_url);

      $options = ['headers' => ['Authorization' => "Bearer $token"]];
      $response = $this->client->request('GET', $api_endpoint, $options);
      $data = json_decode((string) $response->getBody(), TRUE);;

      // Cache just enough for this execution.
      $this->cache->set($cache_key, $data, time() + 30);
      return $data;
    }
    catch (\Exception $e) {
    }
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
