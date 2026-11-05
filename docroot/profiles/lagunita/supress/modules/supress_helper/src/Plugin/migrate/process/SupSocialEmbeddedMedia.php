<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replaces iframes in an HTML string with embeddable media entities.
 *
 * Each iframe becomes an "embeddable" media entity and is swapped for a
 * <drupal-media> tag. Media are matched on later imports by the su_import_lookup
 * field, built from the lookup_id source value and the iframe's position, so
 * books with the same title don't share media, and changed embed codes are
 * updated in place.
 *
 * Configuration:
 * - media_name: (required) Property holding the name for the media entity.
 * - lookup_id: (required) Property holding a value that uniquely identifies
 *   the row, such as the work ID.
 *
 * Usage:
 *
 * @code
 * process:
 *   body/value:
 *     plugin: sup_social_embedded_media
 *     source: html
 *     media_name: '@media_name'
 *     lookup_id: workId
 * @endcode
 */
#[MigrateProcess(
  id: "sup_social_embedded_media"
)]
final class SupSocialEmbeddedMedia extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Media bundle the iframes are stored in.
   */
  const BUNDLE = 'embeddable';

  /**
   * Field holding the embed code.
   */
  const EMBED_FIELD = 'field_media_embeddable_code';

  /**
   * Field holding the unique import key.
   */
  const LOOKUP_FIELD = 'su_import_lookup';

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    foreach (['media_name', 'lookup_id'] as $key) {
      if (empty($this->configuration[$key])) {
        throw new MigrateException("The $key configuration is required.");
      }
    }

    if (!is_string($value) || !preg_match_all('/<iframe\b[^>]*>.*?<\/iframe>/is', $value, $matches)) {
      return $value;
    }
    $iframes = array_values(array_unique($matches[0]));

    $media_name = trim((string) $row->get($this->configuration['media_name']));
    $lookup_id = trim((string) $row->get($this->configuration['lookup_id']));
    if ($media_name === '' || $lookup_id === '') {
      throw new MigrateException('Unable to create embedded media without a media name and lookup id.');
    }

    $media_storage = $this->entityTypeManager->getStorage('media');
    foreach ($iframes as $delta => $iframe) {
      $name = count($iframes) > 1 ? "$media_name: $delta" : $media_name;
      $lookup = "sup_social:$lookup_id:$delta";
      $embed_code = self::forceHttps($iframe);

      $media = $this->findMedia($media_storage, $lookup, $name) ?? $media_storage->create(['bundle' => self::BUNDLE]);
      $media->set('name', $name);
      $media->set(self::LOOKUP_FIELD, $lookup);
      $media->set(self::EMBED_FIELD, $embed_code);
      if ($media->isNew() || $media->hasTranslationChanges()) {
        $media->save();
      }

      $media_tag = sprintf('<drupal-media data-entity-type="media" data-entity-uuid="%s"></drupal-media>', $media->uuid());
      $value = str_replace($iframe, $media_tag, $value);
    }

    return $value;
  }

  /**
   * Find the media created for this iframe on a previous import.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_storage
   *   Media storage.
   * @param string $lookup
   *   Unique import key for the iframe.
   * @param string $name
   *   Media name, used to find media imported before the lookup key existed.
   *
   * @return \Drupal\media\MediaInterface|null
   *   Existing media entity, if found.
   */
  private function findMedia(EntityStorageInterface $media_storage, string $lookup, string $name): ?MediaInterface {
    $ids = $media_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', self::BUNDLE)
      ->condition(self::LOOKUP_FIELD, $lookup)
      ->range(0, 1)
      ->execute();

    // Media imported before the lookup key was added only have a name. Reuse
    // one that hasn't already been claimed by another row with the same name.
    // Only iframe embeds are considered, so editor created media that happen
    // to share a book's title are left alone.
    if (!$ids) {
      $ids = $media_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', self::BUNDLE)
        ->condition('name', $name)
        ->condition(self::EMBED_FIELD, '<iframe', 'CONTAINS')
        ->notExists(self::LOOKUP_FIELD)
        ->range(0, 1)
        ->execute();
    }

    return $ids ? $media_storage->load(reset($ids)) : NULL;
  }

  /**
   * Upgrade http:// urls in the iframe's src attribute to https://.
   *
   * @param string $iframe
   *   Iframe HTML.
   *
   * @return string
   *   Iframe with a secure src url.
   */
  private static function forceHttps(string $iframe): string {
    return preg_replace('/(\ssrc\s*=\s*["\']?)http:\/\//i', '$1https://', $iframe);
  }

}
