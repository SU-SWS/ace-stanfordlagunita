<?php

namespace Drupal\sul_helper\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'sul_branch_selector' field widget.
 *
 * @codeCoverageIgnore
 *
 * @FieldWidget(
 *   id = "sul_branch_selector",
 *   label = @Translation("SUL Branch Selector"),
 *   field_types = {"string"},
 * )
 */
class SulBranchSelectorWidget extends StringTextfieldWidget {

  /**
   * Default cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Guzzle http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

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
      $container->get('cache.default'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CacheBackendInterface $cache, ClientInterface $client) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->cache = $cache;
    $this->client = $client;
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    return ['only_primary' => FALSE];
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['only_primary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only Primary Locations'),
      '#default_value' => $this->getSetting('only_primary'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Only Primary Locations: @primary', ['@primary' => $this->getSetting('only_primary') ? 'Yes' : 'No']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element;
    $element['value'] += [
      '#type' => 'select',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#options' => $this->getSetting('only_primary') ? $this->getPrimaryBranchOptions() : $this->getAllLocationOptions(),
      '#empty_option' => $this->t('- Choose a Branch -'),
    ];

    return $element;
  }

  /**
   * Fetch the json data from library-hours and return the keyed array.
   *
   * @return string[][]
   *   Keyed array of id => label.
   */
  protected function getAllLocationOptions(): array {
    $hours_data = $this->getHoursData();
    $primary_locations = $this->getPrimaryBranchOptions();

    $options = [];
    foreach ($hours_data['included'] as $item) {
      preg_match('/[\w-]+/', $item['id'], $branch);
      $options[$primary_locations[strtolower($branch[0])]][$item['id']] = $item['attributes']['name'];
    }
    return $options;
  }

  /**
   * Fetch the json data from library-hours and return the keyed array.
   *
   * @return string[]
   *   Keyed array of id => label.
   */
  protected function getPrimaryBranchOptions(): array {
    $hours_data = $this->getHoursData();

    $options = [];
    foreach ($hours_data['data'] as $item) {
      $options[strtolower($item['id'])] = $item['attributes']['name'];
    }
    return $options;
  }

  /**
   * Fetch the json data from library-hours and return the API response.
   *
   * @return array
   *   Full API response.
   */
  protected function getHoursData(): array {
    if ($cache = $this->cache->get('sul-hours')) {
      return $cache->data;
    }
    try {
      $response = $this->client->request('GET', 'https://library-hours.stanford.edu/libraries.json');
    }
    catch (\Throwable $e) {
      return [];
    }
    $data = json_decode((string) $response->getBody(), TRUE);
    $this->cache->set('sul-hours', $data, time() + 60 * 60);
    return $data;
  }

}
