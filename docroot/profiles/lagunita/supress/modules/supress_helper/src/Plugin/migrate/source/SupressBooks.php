<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * URL source for press books, supplemented with ebook data from FileMaker.
 *
 * Accepts the same configuration as the "supress_filemaker" source plugin.
 * Ebook retailer links and format flags are fetched once per migration run and
 * added to each row as the "ebook_retailers", "epub" and "pdf" source
 * properties.
 *
 * @code
 * source:
 *   plugin: supress_books
 *   urls:
 *     - 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Web/records'
 * @endcode
 */
#[MigrateSource(id: 'supress_books')]
class SupressBooks extends SupressFileMaker {

  /**
   * Ebook data keyed by work id, loaded on first use.
   *
   * @var array|null
   */
  protected ?array $ebookData = NULL;

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // The feed url contains paging parameters that would change the row hash.
    $row->setSourceProperty('current_feed_url', NULL);

    $work_id = (int) $row->getSourceProperty('work_id_number');
    $ebook = $this->getEbookData()[$work_id] ?? [];
    $row->setSourceProperty('ebook_retailers', $ebook['retailers'] ?? []);
    $row->setSourceProperty('epub', $ebook['epub'] ?? NULL);
    $row->setSourceProperty('pdf', $ebook['pdf'] ?? NULL);

    return parent::prepareRow($row);
  }

  /**
   * Get the ebook data, fetching it from the API once per plugin instance.
   *
   * @return array
   *   Ebook data keyed by work id.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the data can not be fetched. Failing the migration is preferable to
   *   importing every book without its ebook data.
   */
  protected function getEbookData(): array {
    if ($this->ebookData === NULL) {
      try {
        $this->ebookData = $this->pressHelper->getEbookData();
      }
      catch (\Exception $e) {
        throw new MigrateException('Unable to fetch ebook data: ' . $e->getMessage(), 0, $e);
      }
    }
    return $this->ebookData;
  }

}
