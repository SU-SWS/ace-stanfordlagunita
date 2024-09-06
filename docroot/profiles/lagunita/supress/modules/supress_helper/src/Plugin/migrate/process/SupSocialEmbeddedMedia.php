<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a sup_social_embedded_media plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: sup_social_embedded_media
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(id = "sup_social_embedded_media")
 */
final class SupSocialEmbeddedMedia extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
    if (!isset($this->configuration['media_name'])) {
      throw new \Exception('media_name is required.');
    }

    preg_match_all('/(<iframe.*\/iframe>)/', $value, $iframes);
    if (empty($iframes[1])) {
      return $value;
    }

    $media_storage = $this->entityTypeManager->getStorage('media');
    $media_name = $row->get($this->configuration['media_name']);
    foreach ($iframes[1] as $delta => $iframe) {
      $name = count($iframes[1]) > 1 ? $media_name . ': ' . $delta : $media_name;

      $existing_media = $media_storage->loadByProperties(['name' => $name]);
      if ($existing_media) {
        $media = reset($existing_media);
      }
      else {
        $media = $media_storage->create([
          'bundle' => 'embeddable',
          'name' => $name,
          'field_media_embeddable_code' => $iframe,
        ]);
        $media->save();
      }

      $media_tag = "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"{$media->uuid()}\">&nbsp;</drupal-media>";
      $value = str_replace($iframe, $media_tag, $value);
    }

    return $value;
  }

}
