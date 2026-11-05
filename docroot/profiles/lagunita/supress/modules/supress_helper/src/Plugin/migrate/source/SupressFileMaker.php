<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_migrate\Plugin\migrate\source\StanfordUrl;
use Drupal\supress_helper\PressHelperInterface;
use GuzzleHttp\ClientInterface;

/**
 * URL source for FileMaker Data API layouts, paging through every record.
 *
 * Accepts the same configuration as the "url" source plugin. Only the first
 * url is used; it is expanded into one url per page of records when the
 * migration first needs its data, so no API requests are made just by loading
 * the migration.
 *
 * @code
 * source:
 *   plugin: supress_filemaker
 *   urls:
 *     - 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Web/records'
 * @endcode
 */
#[MigrateSource(id: 'supress_filemaker')]
class SupressFileMaker extends StanfordUrl {

  use FileMakerPagedUrlsTrait;

  /**
   * If the source urls have been expanded into paged urls.
   *
   * @var bool
   */
  protected bool $urlsExpanded = FALSE;

  /**
   * {@inheritdoc}
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    DataParserPluginManager $parserPluginManager,
    protected PressHelperInterface $pressHelper,
    protected ClientInterface $httpClient,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $parserPluginManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create($container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate_plus.data_parser'),
      $container->get('supress_helper'),
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceUrls(): array {
    $this->expandUrls();
    return parent::getSourceUrls();
  }

  /**
   * {@inheritdoc}
   */
  public function getDataParserPlugin(): DataParserPluginInterface {
    // The data parser copies the urls from the configuration when created.
    $this->expandUrls();
    return parent::getDataParserPlugin();
  }

  /**
   * Replace the configured url with a url for each page of records.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When no paged urls could be built. Running with an empty source would
   *   otherwise treat every imported item as an orphan.
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   When the record count request fails.
   */
  protected function expandUrls(): void {
    if ($this->urlsExpanded) {
      return;
    }

    $first_url = reset($this->sourceUrls);
    if (!$first_url) {
      throw new MigrateException('No FileMaker url is configured for the migration.');
    }

    $urls = $this->getPagedUrls($first_url);
    if (!$urls) {
      throw new MigrateException(sprintf('No records were found at %s.', $first_url));
    }

    $this->sourceUrls = $this->configuration['urls'] = $urls;
    $this->urlsExpanded = TRUE;
  }

}
