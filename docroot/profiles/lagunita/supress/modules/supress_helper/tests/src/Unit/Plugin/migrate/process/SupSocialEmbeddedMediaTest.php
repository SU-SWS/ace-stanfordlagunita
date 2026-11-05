<?php

namespace Drupal\Tests\supress_helper\Unit\Plugin\migrate\process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\supress_helper\Plugin\migrate\process\SupSocialEmbeddedMedia;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\supress_helper\Plugin\migrate\process\SupSocialEmbeddedMedia
 */
class SupSocialEmbeddedMediaTest extends UnitTestCase {

  /**
   * Existing media keyed by id.
   *
   * @var \Drupal\media\MediaInterface[]
   */
  protected array $media = [];

  /**
   * Field values of each media, keyed by media id.
   */
  protected array $values = [];

  /**
   * Media ids that were saved.
   */
  protected array $saved = [];

  /**
   * Next id for a created media.
   */
  protected int $nextId = 100;

  /**
   * Migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface
   */
  protected $executable;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executable = $this->createMock(MigrateExecutableInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturnCallback(fn() => $this->getQuery());
    $storage->method('load')->willReturnCallback(fn($id) => $this->media[$id] ?? NULL);
    $storage->method('create')->willReturnCallback(fn() => $this->createMedia(NULL, []));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('media')->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Required configuration is validated.
   */
  public function testMissingConfiguration(): void {
    $this->expectException(MigrateException::class);
    $this->getPlugin(['media_name' => 'title'])
      ->transform('<iframe src="x"></iframe>', $this->executable, $this->getRow(), 'body');
  }

  /**
   * Values without iframes are returned untouched.
   */
  public function testNoIframes(): void {
    $plugin = $this->getPlugin();
    $this->assertNull($plugin->transform(NULL, $this->executable, $this->getRow(), 'body'));
    $this->assertSame('<p>Hi</p>', $plugin->transform('<p>Hi</p>', $this->executable, $this->getRow(), 'body'));
    $this->assertEmpty($this->saved);
  }

  /**
   * Iframes become new media keyed by the lookup id.
   */
  public function testCreatesMedia(): void {
    $html = "<p>A</p><IFRAME width=\"5\"\n src=\"http://youtube.com/embed/1?u=http://x\"></IFRAME><iframe src='https://vimeo.com/2'> </iframe>";
    $result = $this->getPlugin()->transform($html, $this->executable, $this->getRow(), 'body');

    $this->assertCount(2, $this->saved);
    [$first, $second] = array_map(fn($id) => $this->values[$id], $this->saved);
    $this->assertSame('Book: 0', $first['name']);
    $this->assertSame('sup_social:123:0', $first['su_import_lookup']);
    $this->assertStringContainsString('src="https://youtube.com/embed/1?u=http://x"', $first['field_media_embeddable_code']);
    $this->assertSame('Book: 1', $second['name']);
    $this->assertSame('sup_social:123:1', $second['su_import_lookup']);

    $this->assertStringNotContainsStringIgnoringCase('<iframe', $result);
    $this->assertStringStartsWith('<p>A</p><drupal-media data-entity-type="media" data-entity-uuid="uuid-100"></drupal-media>', $result);
    $this->assertStringContainsString('data-entity-uuid="uuid-101"', $result);
  }

  /**
   * Media are reused by lookup and only saved when the embed changes.
   */
  public function testReusesMedia(): void {
    $iframe = '<iframe src="https://youtube.com/embed/1"></iframe>';
    $this->createMedia(5, [
      'name' => 'Book',
      'su_import_lookup' => 'sup_social:123:0',
      'field_media_embeddable_code' => $iframe,
    ]);
    // A media with the same name belonging to a different book.
    $this->createMedia(6, ['name' => 'Book', 'su_import_lookup' => 'sup_social:999:0']);

    $plugin = $this->getPlugin();
    $result = $plugin->transform($iframe, $this->executable, $this->getRow(), 'body');
    $this->assertStringContainsString('uuid-5', $result);
    $this->assertEmpty($this->saved);

    $new_iframe = '<iframe src="https://youtube.com/embed/2"></iframe>';
    $plugin->transform($new_iframe, $this->executable, $this->getRow(), 'body');
    $this->assertSame([5], $this->saved);
    $this->assertSame($new_iframe, $this->values[5]['field_media_embeddable_code']);
  }

  /**
   * Media imported before the lookup key existed are claimed by name.
   */
  public function testClaimsLegacyMedia(): void {
    // Editor created media with the book's name, not from the importer.
    $this->createMedia(8, ['name' => 'Book', 'field_media_embeddable_code' => '<script src="x"></script>']);
    $this->createMedia(7, ['name' => 'Book', 'field_media_embeddable_code' => '<iframe src="https://old"></iframe>']);
    $result = $this->getPlugin()->transform('<iframe src="https://a"></iframe>', $this->executable, $this->getRow(), 'body');

    $this->assertStringContainsString('uuid-7', $result);
    $this->assertSame('sup_social:123:0', $this->values[7]['su_import_lookup']);
  }

  /**
   * Get the process plugin.
   */
  protected function getPlugin(array $configuration = ['media_name' => 'title', 'lookup_id' => 'workId']): SupSocialEmbeddedMedia {
    return SupSocialEmbeddedMedia::create(\Drupal::getContainer(), $configuration, 'sup_social_embedded_media', []);
  }

  /**
   * Get a migrate row.
   */
  protected function getRow(): Row {
    return new Row(['title' => 'Book', 'workId' => 123]);
  }

  /**
   * Create a mock media entity that records its field values.
   */
  protected function createMedia(?int $id, array $values): MediaInterface {
    $id ??= $this->nextId++;
    $this->values[$id] = $values;
    $original = $values;
    $is_new = !isset($this->media[$id]) && !$values;

    $media = $this->createMock(MediaInterface::class);
    $media->method('uuid')->willReturn("uuid-$id");
    $media->method('isNew')->willReturnCallback(fn() => $is_new);
    $media->method('set')->willReturnCallback(function ($field, $value) use ($id, $media) {
      $this->values[$id][$field] = $value;
      return $media;
    });
    $media->method('hasTranslationChanges')->willReturnCallback(fn() => $this->values[$id] != $original);
    $media->method('save')->willReturnCallback(function () use ($id, &$original, &$is_new) {
      $this->saved[] = $id;
      $original = $this->values[$id];
      $is_new = FALSE;
      return 1;
    });
    return $this->media[$id] = $media;
  }

  /**
   * Get an entity query that filters the mock media.
   */
  protected function getQuery(): QueryInterface {
    $conditions = [];
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('condition')->willReturnCallback(function ($field, $value, $operator = '=') use ($query, &$conditions) {
      $conditions[$field] = [$value, $operator];
      return $query;
    });
    $query->method('notExists')->willReturnCallback(function ($field) use ($query, &$conditions) {
      $conditions[$field] = [NULL, '='];
      return $query;
    });
    $query->method('execute')->willReturnCallback(function () use (&$conditions) {
      unset($conditions['bundle']);
      foreach ($this->values as $id => $values) {
        if (!isset($this->media[$id]) || $this->media[$id]->isNew()) {
          continue;
        }
        foreach ($conditions as $field => [$value, $operator]) {
          $actual = $values[$field] ?? NULL;
          if ($operator == 'CONTAINS' ? !str_contains((string) $actual, $value) : $actual !== $value) {
            continue 2;
          }
        }
        return [$id => $id];
      }
      return [];
    });
    return $query;
  }

}
