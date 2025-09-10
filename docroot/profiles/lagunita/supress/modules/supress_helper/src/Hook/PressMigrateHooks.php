<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\supress_helper\PressFilemakerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hooks that modify the behavior of migrations.
 */
class PressMigrateHooks {

  public function __construct(
    #[Autowire(service: 'supress_helper.filemaker')]
    protected readonly PressFilemakerInterface $filemaker
  ) {}

  #[Hook('migrate_prepare_row')]
  public function migratePrepareRow(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
    $row->setSourceProperty('current_feed_url', NULL);
    $id_map = $row->getIdMap();
    if (!isset($id_map['sourceid1'])) {
      return;
    }

    $retailers = $this->filemaker->getEbookRetailers();
    $retailers = array_filter($retailers, fn($retailer) => (int) $retailer['work_id_number'] == (int) $id_map['sourceid1']);
    $row->setSourceProperty('ebook_retailers', array_values($retailers));

    $book_formats = $this->filemaker->getEbookFormats();
    $row->setSourceProperty('epub', $book_formats[$id_map['sourceid1']]['epub'] ?? NULL);
    $row->setSourceProperty('pdf', $book_formats[$id_map['sourceid1']]['pdf'] ?? NULL);
  }

}
