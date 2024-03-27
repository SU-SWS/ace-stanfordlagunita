<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate_plus\authentication;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\AuthenticationPluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides FileMaker authentication for the HTTP resource.
 *
 * source:
 *   authentication:
 *     plugin: press_filemaker
 *     token_url: '[filemaker url]'
 *     client_id: '[api user]'
 *     client_secret: '[api password'
 *
 * @Authentication(
 *   id = "press_filemaker",
 *   title = @Translation("Press Filemaker")
 * )
 */
class FileMakerAuth extends AuthenticationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): AuthenticationPluginBase {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('http_client'));
  }

  /**
   * File maker auth plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzle client servicde.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationOptions(): array {
    $response = $this->client->request('POST', $this->configuration['token_url'], [
      'verify' => FALSE,
      'headers' => ['Content-Type' => 'application/json'],
      'auth' => [
        $this->configuration['client_id'],
        $this->configuration['client_secret'],
      ],
    ]);
    $token_response = json_decode((string) $response->getBody(), TRUE, 513, JSON_THROW_ON_ERROR);

    return [
      'verify' => FALSE,
      'headers' => [
        'Authorization' => 'Bearer ' . $token_response['response']['token'],
      ],
    ];
  }

}