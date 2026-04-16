<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\supress_helper\PressHelperInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a source plugin for the SuPress Social Media FileMaker API.
 *
 * Fetches social media records from the FileMaker Data API across all paged
 * URLs, combines the raw records, groups them by work ID, and builds an HTML
 * representation of each group.
 *
 * Authentication and URL pagination are delegated to the supress_helper
 * service. The source endpoint can be overridden via the 'url' configuration
 * key; otherwise the default FileMaker endpoint is used.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: supress_social_media
 * @endcode
 *
 * Optionally override the default endpoint:
 *
 * @code
 * source:
 *   plugin: supress_social_media
 *   url: 'https://example.com/fmi/data/v2/databases/Web/layouts/SocialMedia/records'
 * @endcode
 */
#[MigrateSource(id: 'supress_social_media')]
class SupressSocialMedia extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The default FileMaker Data API endpoint for Social Media records.
   */
  const ENDPOINT = 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/SocialMedia/records';

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    protected ClientInterface $httpClient,
    protected PressHelperInterface $pressHelper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('http_client'),
      $container->get('supress_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return $this->configuration['url'] ?? self::ENDPOINT;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return ['workId' => ['type' => 'integer']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'workId' => 'Work ID Number',
      'html' => 'Social Media HTML Content',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator(): \ArrayIterator {
    $url = $this->configuration['url'] ?? self::ENDPOINT;
    $token = $this->pressHelper->getApiToken();

    $allRecords = [];
    foreach ($this->pressHelper->getMigrationUrls($url) as $pagedUrl) {
      $response = $this->httpClient->request('GET', $pagedUrl, [
        'headers' => ['Authorization' => 'Bearer ' . $token],
      ]);
      $body = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $allRecords = array_merge($allRecords, $body['response']['data'] ?? []);
    }

    return new \ArrayIterator($this->processRecords($allRecords));
  }

  /**
   * Transforms raw FileMaker records into grouped, HTML-enriched items.
   *
   * Records are grouped by work_id_number. Each record's fieldData is used
   * to build an HTML snippet; snippets for the same work ID are concatenated.
   * iframes missing a title attribute receive one derived from the record title
   * to satisfy accessibility requirements.
   *
   * @param array $records
   *   Raw records from the FileMaker Data API response.
   *
   * @return array
   *   Array of items with 'workId' and 'html' keys, indexed from zero.
   */
  public function processRecords(array $records): array {
    $data = [];

    foreach ($records as $record) {
      $item = $record['fieldData'] ?? [];

      if (empty($item['title']) || empty($item['work_id_number'])) {
        continue;
      }

      $work_id = $item['work_id_number'];
      $item_html = "<h2><a href=\"{$item['url']}\">{$item['title']}</a></h2><p>";

      if (!empty($item['description'])) {
        preg_match_all('/(<iframe.*?>)/', $item['description'], $iframes);
        if (!empty($iframes[1])) {
          foreach ($iframes[1] as $iframe) {
            if (!str_contains($iframe, ' title="')) {
              $iframe_title = Html::escape($item['title']);
              $modified_iframe = str_replace('<iframe ', "<iframe title=\"$iframe_title\" ", $iframe);
              $item['description'] = str_replace($iframe, $modified_iframe, $item['description']);
            }
          }
        }
        $item_html .= $item['description'];
      }

      if (!empty($item['source'])) {
        $item_html .= "<br />&mdash;{$item['source']}";
      }

      $item_html .= '</p>';
      $item_html = str_replace('<p></p>', '', $item_html);

      if (isset($data[$work_id])) {
        $data[$work_id]['html'] .= $item_html;
      }
      else {
        $data[$work_id] = [
          'workId' => $work_id,
          'html' => $item_html,
        ];
      }
    }

    return array_values($data);
  }

}
