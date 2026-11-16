<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\supress_helper\PressHelperInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A Drush commandfile.
 */
final class PressCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * FileMaker records url for the Web layout, one record per book.
   */
  const string BOOKS_ENDPOINT = 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Web/records';

  /**
   * Number of book records to request per page.
   */
  const int BOOKS_PAGE_SIZE = 5000;

  protected $pressLogger;

  /**
   * Constructs a PressCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ClientInterface $httpClient,
    LoggerChannelFactoryInterface $loggerFactory,
    #[Autowire(service: 'supress_helper')]
    private readonly PressHelperInterface $pressHelper,
    private readonly Connection $database,
    private readonly MigrationPluginManagerInterface $migrationManager,
  ) {
    parent::__construct();
    $this->pressLogger = $loggerFactory->get('supress');
  }

  /**
   * Unpublish books that are no longer in the FileMaker API data.
   */
  #[CLI\Command(name: 'supress:unpublish-orphans')]
  #[CLI\Option(name: 'dry-run', description: 'List the books that would be unpublished without changing them.')]
  #[CLI\Usage(name: 'drush supress:unpublish-orphans --dry-run', description: 'List the published books that are not in the FileMaker API data.')]
  public function supUnpublishOprhans(array $options = ['dry-run' => FALSE]): void {
    $this->unpublishOrphanBooks((bool) $options['dry-run']);
  }

  /**
   * Run all the orphan cleanups: books, then ancillary nodes, then press data.
   *
   * Books are unpublished first, so the ancillary nodes and press entities of
   * those books are cleaned up in the same run.
   */
  #[CLI\Command(name: 'supress:cleanup')]
  #[CLI\Option(name: 'delete', description: 'Delete orphaned ancillary nodes instead of unpublishing them.')]
  #[CLI\Option(name: 'dry-run', description: 'List what each cleanup would change without changing anything.')]
  #[CLI\Usage(name: 'drush supress:cleanup --dry-run', description: 'List the books, ancillary nodes and press entities that would be cleaned up.')]
  #[CLI\Usage(name: 'drush supress:cleanup -y', description: 'Run all cleanups without prompting.')]
  #[CLI\Usage(name: 'drush supress:cleanup --delete -y', description: 'Run all cleanups, deleting orphaned ancillary nodes, without prompting.')]
  public function supCleanup(array $options = ['delete' => FALSE, 'dry-run' => FALSE]): void {
    $dry_run = (bool) $options['dry-run'];

    $this->io()->section('Books');
    $book_ids = $this->unpublishOrphanBooks($dry_run);

    // A dry run leaves those books published, so the next steps treat them as
    // unpublished to show what a real run would do.
    $unpublished_book_ids = $dry_run ? $book_ids : [];

    $this->io()->section('Book ancillary nodes');
    $this->cleanAncillaryOrphans((bool) $options['delete'], $dry_run, $unpublished_book_ids);

    $this->io()->section('Press entities');
    $this->cleanPressOrphans($dry_run, $unpublished_book_ids);
  }

  /**
   * Unpublish published books that are not in the FileMaker API data.
   *
   * @param bool $dry_run
   *   List the books instead of unpublishing them.
   *
   * @return int[]
   *   Ids of the books that were, or would be, unpublished.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   *   When the book list can't be fetched in full from the API.
   */
  protected function unpublishOrphanBooks(bool $dry_run): array {
    $work_ids = $this->getApiWorkIds();

    $node_storage = $this->entityTypeManager->getStorage('node');
    $ids = array_map('intval', array_values($node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'sup_book')
      ->condition('status', 1)
      ->condition('sup_book_work_id_number', $work_ids, 'NOT IN')
      ->execute()));

    if (!$ids) {
      $this->io()->success('No orphaned books found.');
      return [];
    }

    $rows = [];
    foreach (array_chunk($ids, 50) as $chunk) {
      /** @var \Drupal\node\NodeInterface $node */
      foreach ($node_storage->loadMultiple($chunk) as $node) {
        if ($dry_run) {
          $rows[] = [$node->id(), $node->label(), $node->get('sup_book_work_id_number')->value];
          continue;
        }
        $this->pressLogger->notice('Unpublished orphan book: %title', ['%title' => $node->label()]);
        $node->setUnpublished()->save();
      }
      $node_storage->resetCache($chunk);
    }

    if ($dry_run) {
      $this->printTable(['Nid', 'Title', 'Work id'], $rows);
      $this->io()->note(sprintf('Dry run: would unpublish %d orphaned book(s). Nothing was changed.', count($ids)));
    }
    else {
      $this->io()->success(sprintf('Unpublished %d orphaned book(s).', count($ids)));
    }
    return $ids;
  }

  /**
   * Unpublish or delete book ancillary nodes whose book is gone or unpublished.
   *
   * An ancillary node is an orphan when its book reference is empty, points
   * to a node that no longer exists, or points to an unpublished book.
   */
  #[CLI\Command(name: 'supress:ancillary-orphans')]
  #[CLI\Option(name: 'delete', description: 'Delete the orphaned ancillary nodes instead of unpublishing them.')]
  #[CLI\Option(name: 'dry-run', description: 'List the orphaned ancillary nodes without changing them.')]
  #[CLI\Usage(name: 'drush supress:ancillary-orphans', description: 'Unpublish published ancillary nodes that have no published book.')]
  #[CLI\Usage(name: 'drush supress:ancillary-orphans --delete', description: 'Delete all ancillary nodes that have no published book.')]
  #[CLI\Usage(name: 'drush supress:ancillary-orphans --delete --dry-run', description: 'List the ancillary nodes that --delete would delete.')]
  public function supAncillaryOrphans(array $options = ['delete' => FALSE, 'dry-run' => FALSE]): void {
    $this->cleanAncillaryOrphans((bool) $options['delete'], (bool) $options['dry-run']);
  }

  /**
   * Unpublish or delete ancillary nodes that have no published book.
   *
   * @param bool $delete
   *   Delete the nodes instead of unpublishing them.
   * @param bool $dry_run
   *   List the nodes instead of changing them.
   * @param int[] $unpublished_book_ids
   *   Published books to treat as unpublished.
   */
  protected function cleanAncillaryOrphans(bool $delete, bool $dry_run, array $unpublished_book_ids = []): void {
    $ids = $this->getOrphanedAncillaryIds(!$delete, $unpublished_book_ids);

    if (!$ids) {
      $this->io()->success('No orphaned ancillary nodes found.');
      return;
    }

    $action = $delete ? 'Delete' : 'Unpublish';
    $node_storage = $this->entityTypeManager->getStorage('node');

    if ($dry_run) {
      $rows = [];
      foreach (array_chunk($ids, 50) as $chunk) {
        /** @var \Drupal\node\NodeInterface $node */
        foreach ($node_storage->loadMultiple($chunk) as $node) {
          $book_id = $node->get('sup_ancillary_book')->target_id;
          $book = $book_id ? $node_storage->load($book_id) : NULL;
          $reason = match (TRUE) {
            !$book_id => 'No book',
            !$book => 'Book deleted',
            in_array($book_id, $unpublished_book_ids) => 'Book would be unpublished',
            default => 'Book unpublished',
          };
          $rows[] = [
            $node->id(),
            $node->label(),
            $node->isPublished() ? 'Published' : 'Unpublished',
            $book_id ?: '',
            $reason,
          ];
        }
        $node_storage->resetCache($chunk);
      }
      $this->printTable(['Nid', 'Title', 'Status', 'Book nid', 'Reason'], $rows);
      $this->io()->note(sprintf('Dry run: %s %d orphaned ancillary node(s). Nothing was changed.', $delete ? 'would delete' : 'would unpublish', count($ids)));
      return;
    }

    if (!$this->io()->confirm(sprintf('%s %d orphaned ancillary node(s)?', $action, count($ids)), FALSE)) {
      return;
    }

    foreach (array_chunk($ids, 50) as $chunk) {
      /** @var \Drupal\node\NodeInterface[] $nodes */
      $nodes = $node_storage->loadMultiple($chunk);
      foreach ($nodes as $node) {
        $this->pressLogger->notice('@action orphan book ancillary: %title (@nid)', [
          '@action' => $delete ? 'Deleted' : 'Unpublished',
          '%title' => $node->label(),
          '@nid' => $node->id(),
        ]);
        if (!$delete) {
          $node->setUnpublished()->save();
        }
      }
      if ($delete) {
        $node_storage->delete($nodes);
      }
      $node_storage->resetCache($chunk);
    }

    $this->io()->success(sprintf('%s %d orphaned ancillary node(s).', $delete ? 'Deleted' : 'Unpublished', count($ids)));
  }

  /**
   * Get the ids of ancillary nodes that don't reference a published book.
   *
   * @param bool $published_only
   *   Only return ancillary nodes that are currently published.
   * @param int[] $unpublished_book_ids
   *   Published books to treat as unpublished.
   *
   * @return int[]
   *   Ancillary node ids.
   */
  protected function getOrphanedAncillaryIds(bool $published_only, array $unpublished_book_ids = []): array {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $book_ids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'sup_book')
      ->condition('status', 1)
      ->execute();
    $book_ids = array_diff($book_ids, $unpublished_book_ids);

    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'sup_book_ancillary');
    if ($published_only) {
      $query->condition('status', 1);
    }

    // With no published books, every ancillary node is an orphan. Otherwise,
    // the reference is missing, or points to a deleted or unpublished node.
    if ($book_ids) {
      $orphans = $query->orConditionGroup()
        ->notExists('sup_ancillary_book')
        ->condition('sup_ancillary_book', array_values($book_ids), 'NOT IN');
      $query->condition($orphans);
    }

    return array_values($query->execute());
  }

  /**
   * Delete press entities (awards, prices) that have no published book.
   *
   * A press entity is an orphan when no published book has its work id, and
   * no published book references it in its awards or price data fields.
   */
  #[CLI\Command(name: 'supress:press-orphans')]
  #[CLI\Option(name: 'dry-run', description: 'List the orphaned press entities without deleting them.')]
  #[CLI\Usage(name: 'drush supress:press-orphans', description: 'Delete press entities that have no published book.')]
  #[CLI\Usage(name: 'drush supress:press-orphans --dry-run', description: 'List the press entities that would be deleted.')]
  public function supPressOrphans(array $options = ['dry-run' => FALSE]): void {
    $this->cleanPressOrphans((bool) $options['dry-run']);
  }

  /**
   * Delete press entities that have no published book.
   *
   * @param bool $dry_run
   *   List the entities instead of deleting them.
   * @param int[] $unpublished_book_ids
   *   Published books to treat as unpublished.
   */
  protected function cleanPressOrphans(bool $dry_run, array $unpublished_book_ids = []): void {
    $ids = $this->getOrphanedPressIds($unpublished_book_ids);

    if (!$ids) {
      $this->io()->success('No orphaned press entities found.');
      return;
    }

    $press_storage = $this->entityTypeManager->getStorage('press');

    if ($dry_run) {
      $rows = [];
      foreach (array_chunk($ids, 50) as $chunk) {
        /** @var \Drupal\supress_helper\Entity\PressEntityInterface $entity */
        foreach ($press_storage->loadMultiple($chunk) as $entity) {
          $rows[] = [
            $entity->id(),
            $entity->bundle(),
            $entity->label(),
            $entity->get('work_id')->value,
          ];
        }
        $press_storage->resetCache($chunk);
      }
      $this->printTable(['Id', 'Bundle', 'Title', 'Work id'], $rows);
      $this->io()->note(sprintf('Dry run: would delete %d orphaned press entities. Nothing was changed.', count($ids)));
      return;
    }

    if (!$this->io()->confirm(sprintf('Delete %d orphaned press entities?', count($ids)), FALSE)) {
      return;
    }

    // Migrations that create press entities. Their map rows for the deleted
    // entities are removed too, so a later import recreates the entity if the
    // book comes back, instead of skipping the unchanged source row.
    $id_maps = [];
    foreach ($this->migrationManager->createInstances([]) as $migration) {
      if (($migration->getDestinationConfiguration()['plugin'] ?? NULL) === 'entity:press') {
        $id_maps[] = $migration->getIdMap();
      }
    }

    foreach (array_chunk($ids, 50) as $chunk) {
      $entities = $press_storage->loadMultiple($chunk);
      foreach ($entities as $entity) {
        $this->pressLogger->notice('Deleted orphan press @bundle: %title (@id)', [
          '@bundle' => $entity->bundle(),
          '%title' => $entity->label(),
          '@id' => $entity->id(),
        ]);
      }
      $press_storage->delete($entities);
      foreach ($chunk as $id) {
        foreach ($id_maps as $id_map) {
          $id_map->deleteDestination(['id' => $id]);
        }
      }
      $press_storage->resetCache($chunk);
    }

    $this->io()->success(sprintf('Deleted %d orphaned press entities.', count($ids)));
  }

  /**
   * Get the ids of press entities that no published book uses.
   *
   * @param int[] $unpublished_book_ids
   *   Published books to treat as unpublished.
   *
   * @return int[]
   *   Press entity ids.
   */
  protected function getOrphanedPressIds(array $unpublished_book_ids = []): array {
    $press_type = $this->entityTypeManager->getDefinition('press');
    $press_id = $press_type->getKey('id');

    $query = $this->database->select($press_type->getBaseTable(), 'p')
      ->fields('p', [$press_id]);
    $query->notExists($this->publishedBookSubquery('sup_book_work_id_number', 'value', 'work_id', $unpublished_book_ids));
    $query->notExists($this->publishedBookSubquery('sup_book_awards', 'target_id', $press_id, $unpublished_book_ids));
    $query->notExists($this->publishedBookSubquery('sup_book_price_data', 'target_id', $press_id, $unpublished_book_ids));

    return array_map('intval', $query->execute()->fetchCol());
  }

  /**
   * Build a subquery matching published books whose field equals a value.
   *
   * @param string $field_name
   *   Book field name.
   * @param string $property
   *   Field property to compare.
   * @param string $outer_column
   *   Column of the outer press query (alias "p") to compare the property to.
   * @param int[] $unpublished_book_ids
   *   Published books to leave out of the match.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Subquery for a NOT EXISTS condition.
   */
  protected function publishedBookSubquery(string $field_name, string $property, string $outer_column, array $unpublished_book_ids = []): SelectInterface {
    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_type = $this->entityTypeManager->getDefinition('node');
    $mapping = $node_storage->getTableMapping();
    $column = $mapping->getColumnNames($field_name)[$property];

    $subquery = $this->database->select($mapping->getFieldTableName($field_name), 'f');
    $subquery->addExpression('1');
    $subquery->join($node_type->getDataTable(), 'n', sprintf('[n].[%s] = [f].[entity_id] AND [n].[%s] = [f].[langcode]', $node_type->getKey('id'), $node_type->getKey('langcode')));
    $subquery->condition('n.' . $node_type->getKey('bundle'), 'sup_book');
    $subquery->condition('n.' . $node_type->getKey('status'), 1);
    if ($unpublished_book_ids) {
      $subquery->condition('n.' . $node_type->getKey('id'), $unpublished_book_ids, 'NOT IN');
    }
    $subquery->where(sprintf('[f].[%s] = [p].[%s]', $column, $outer_column));
    return $subquery;
  }

  /**
   * Print a table, with each cell reduced to a single line of plain text.
   *
   * Imported titles can contain carriage returns and other control
   * characters, which break the table layout in a terminal.
   *
   * @param string[] $headers
   *   Column headers.
   * @param array[] $rows
   *   Table rows.
   */
  protected function printTable(array $headers, array $rows): void {
    $rows = array_map(fn(array $row) => array_map([$this, 'singleLine'], $row), $rows);
    $this->io()->table($headers, $rows);
  }

  /**
   * Reduce a value to a single line of plain text.
   *
   * FileMaker stores line breaks as carriage returns, so a title like
   * "Book Award\r-Science category" was entered as two lines. A line that
   * starts with a dash becomes " - ", so it doesn't print as "Award -Science".
   * Every other run of whitespace or control characters becomes one space.
   *
   * @param mixed $value
   *   Table cell value.
   *
   * @return string
   *   The value on one line.
   */
  protected function singleLine(mixed $value): string {
    $value = preg_replace('/\s*[\r\n]+\s*-\s*/u', ' - ', (string) $value);
    return trim(preg_replace('/[\s\p{Cc}]+/u', ' ', $value));
  }

  /**
   * Get the work id of every book in the FileMaker Web layout.
   *
   * The list is fetched fresh on every call. Books missing from it get
   * unpublished, so a stale or partial list would unpublish good books.
   *
   * @return string[]
   *   Work id numbers.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   *   When unable to authenticate, or the API returns no or only some books.
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   When a request fails.
   * @throws \JsonException
   *   When a response isn't valid JSON.
   */
  protected function getApiWorkIds(): array {
    $token = $this->pressHelper->getApiToken();
    if (!$token) {
      throw new CommandFailedException('Unable to authenticate with the FileMaker API. Check the FileMaker credentials in the site settings.');
    }

    $work_ids = [];
    $received = 0;
    $total = NULL;
    // FileMaker offsets are 1-based.
    for ($offset = 1; $total === NULL || $offset <= $total; $offset += self::BOOKS_PAGE_SIZE) {
      $this->io()->text(sprintf('Fetching books %d to %d from FileMaker.', $offset, $offset + self::BOOKS_PAGE_SIZE - 1));
      $response = $this->httpClient->request('GET', self::BOOKS_ENDPOINT, [
        'query' => ['_limit' => self::BOOKS_PAGE_SIZE, '_offset' => $offset],
        'headers' => ['Authorization' => 'Bearer ' . $token],
        'timeout' => 60,
      ]);
      $body = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $records = $body['response']['data'] ?? [];
      $total ??= (int) ($body['response']['dataInfo']['totalRecordCount'] ?? 0);

      // Stop rather than loop forever if a page comes back empty early.
      if (!$records) {
        break;
      }
      $received += count($records);
      foreach ($records as $record) {
        $work_id = trim((string) ($record['fieldData']['work_id_number'] ?? ''));
        if ($work_id !== '') {
          $work_ids[$work_id] = $work_id;
        }
      }
    }

    if (!$work_ids) {
      throw new CommandFailedException('The FileMaker API returned no books.');
    }
    if ($received < $total) {
      throw new CommandFailedException(sprintf('The FileMaker API returned %d of %d books. Stopping so books missing from the list are not unpublished.', $received, $total));
    }

    return array_values($work_ids);
  }

}
