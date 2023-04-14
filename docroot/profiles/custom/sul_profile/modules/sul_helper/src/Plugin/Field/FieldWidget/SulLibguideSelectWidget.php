<?php

namespace Drupal\sul_helper\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'sul_libguide_select' field widget.
 *
 * @FieldWidget(
 *   id = "sul_libguide_select",
 *   label = @Translation("LibGuide Select"),
 *   field_types = {
 *     "integer"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SulLibGuideSelectWidget extends OptionsSelectWidget {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('http_client'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ClientInterface $client, CacheBackendInterface $cache) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->client = $client;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // If there are no options or an error occurred, use a number field.
    if (empty($this->options)) {
      $element['#type'] = 'number';
      $element['#default_value'] = $items[$delta]->value;
    }
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  protected function getOptions(FieldableEntityInterface $entity): array {
    $this->options = [];
    if ($cache = $this->cache->get('sul_libguide_options')) {
      $this->options = $cache->data;
      return $this->options;
    }

    try {
      $access_token = $this->getAccessToken();
      $response = $this->client->request('GET', 'https://lgapi-us.libapps.com/1.2/subjects', ['headers' => ['Authorization' => "Bearer $access_token"]]);
      $subjects = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\Throwable $e) {
      return $this->options;
    }

    foreach ($subjects as $subject) {
      $this->options[$subject['id']] = $subject['name'];
    }
    $this->cache->set('sul_libguide_options', $this->options);
    return $this->options;
  }

  /**
   * Get the OAuth2 access token.
   *
   * @return string
   *   The access token.
   */
  protected function getAccessToken(): string {
    if ($token = $this->cache->get('sul_libguide_access_token')) {
      return $token->data;
    }

    $response = $this->client->request('POST', 'https://lgapi-us.libapps.com/1.2/oauth/token', [
      'form_params' => [
        'client_id' => Settings::get('library_libguide_client_id'),
        'client_secret' => Settings::get('library_libguide_client_secret'),
        'grant_type' => 'client_credentials',
      ],
    ]);
    $response_body = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    $this->cache->set('sul_libguide_access_token', $response_body['access_token'], time() + $response_body['expires_in']);
    return $response_body['access_token'];
  }

}
