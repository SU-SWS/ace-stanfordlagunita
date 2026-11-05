<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate_plus\authentication;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Attribute\Authentication;
use Drupal\migrate_plus\AuthenticationPluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides FileMaker authentication for the HTTP resource.
 *
 * A single FileMaker session is created per plugin instance and its token is
 * reused for every request the data fetcher makes.
 *
 * @code
 * source:
 *   authentication:
 *     plugin: press_filemaker
 *     token_url: '[filemaker sessions url]'
 *     client_id: '[api user]'
 *     client_secret: '[api password]'
 * @endcode
 */
#[Authentication(
  id: 'press_filemaker',
  title: new TranslatableMarkup('Press Filemaker'),
)]
final class FileMakerAuth extends AuthenticationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * FileMaker session token, once authenticated.
   *
   * @var string|null
   */
  protected ?string $token = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('http_client'));
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
   *   Guzzle client service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationOptions($url): array {
    return [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->getToken(),
      ],
    ];
  }

  /**
   * Get the session token, logging into FileMaker on the first call.
   *
   * The data fetcher asks for authentication options before every request, so
   * the token is kept to avoid opening a new FileMaker session for each page.
   *
   * @return string
   *   Session token.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the plugin is misconfigured or the API doesn't return a token.
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   When the session request fails.
   * @throws \JsonException
   *   When the response is not valid JSON.
   */
  protected function getToken(): string {
    if ($this->token) {
      return $this->token;
    }

    // The migration group config ships with "[client_id]" style placeholders
    // that are replaced by config page overrides once credentials are saved.
    $missing = array_filter(['token_url', 'client_id', 'client_secret'], fn($key) => empty($this->configuration[$key]) || preg_match('/^\[.*\]$/', (string) $this->configuration[$key]));
    if ($missing) {
      throw new MigrateException(sprintf('FileMaker authentication is missing required settings: %s', implode(', ', $missing)));
    }

    $response = $this->client->request('POST', $this->configuration['token_url'], [
      'headers' => ['Content-Type' => 'application/json'],
      'auth' => [
        $this->configuration['client_id'],
        $this->configuration['client_secret'],
      ],
      'timeout' => 30,
    ]);
    $token_response = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);

    if (empty($token_response['response']['token']) || !is_string($token_response['response']['token'])) {
      throw new MigrateException('FileMaker did not return a session token.');
    }
    $this->token = $token_response['response']['token'];
    return $this->token;
  }

}
