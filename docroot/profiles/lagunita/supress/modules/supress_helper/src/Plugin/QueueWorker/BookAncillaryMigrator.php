<?php

namespace Drupal\supress_helper\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\FileInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cron queue worker for migrating book ancillary contents.
 *
 * @QueueWorker(
 *   id = "press_book_ancillary_migrator",
 *   title = @Translation("Book cover downloader"),
 *   cron = {"time" = 60}
 * )
 */
class BookAncillaryMigrator extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * Cron queue worker constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzle service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Drupal file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ClientInterface $client, protected FileSystemInterface $fileSystem, protected EntityTypeManagerInterface $entityTypeManager, protected LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {

    $ns = $this->entityTypeManager->getStorage('node');
    $ps = $this->entityTypeManager->getStorage('paragraph');

    $dir = 'public://media/image/gallery';
    $this->fileSystem->prepareDirectory($dir, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    /** @var \Drupal\paragraphs\ParagraphInterface $default_row */
    $row = $ps->create(['type' => 'stanford_layout']);
    $row->setBehaviorSettings('layout_paragraphs', [
      'layout' => 'layout_paragraphs_1_column',
      'config' => [],
      'parent_uuid' => NULL,
      'region' => NULL,
    ]);

    $book_id = $ns->getQuery()
      ->accessCheck(FALSE)
      ->condition('sup_book_work_id_number', $data['work_id'])
      ->execute();
    if (!$book_id) {
      $this->logger->get('supress')
        ->error('missing book %work_id', ['%work_id' => $data['work_id']]);
      return;
    }

    if (isset($data['html'])) {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $ps->create([
        'type' => 'stanford_wysiwyg',
        'su_wysiwyg_text' => [
          'value' => $this->processHtml($data['html']),
          'format' => 'stanford_html',
        ],
      ]);
    }

    if (isset($data['files'])) {
      $files = [];
      foreach ($data['files'] as $file_url) {
        $file_dir = "public://media/{$data['work_id']}";

        $media = $this->importFileMedia($file_url, $file_dir);
        $files[] = $media->id();
      }

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $ps->create([
        'type' => 'sup_file_list',
        'sup_file_list_files' => $files,
        'sup_file_list_display' => 'icons',
      ]);
    }

    if (isset($data['images'])) {
      $images = [];
      foreach ($data['images'] as $image_url) {
        $file_dir = "$dir/{$data['work_id']}";

        $media = $this->importImageMedia($image_url, $file_dir, 'stanford_gallery_images');
        $images[] = $media->id();
      }

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $ps->create([
        'type' => 'stanford_gallery',
        'su_gallery_images' => $images,
      ]);
    }

    if (!isset($paragraph)) {
      $row->delete();
      return;
    }

    $paragraph->setBehaviorSettings('layout_paragraphs', [
      'parent_uuid' => $row->uuid(),
      'region' => 'main',
    ]);
    $paragraph->save();

    $ns->create([
      'type' => 'sup_book_ancillary',
      'title' => $data['title'],
      'sup_ancillary_book' => reset($book_id),
      'sup_ancillary_paragraphs' => [
        [
          'target_id' => $row->id(),
          'entity' => $row,
        ],
        [
          'target_id' => $paragraph->id(),
          'entity' => $paragraph,
        ],
      ],
    ])->save();
  }

  protected function processHtml($html) {
    $html = preg_replace('/> +?</', '><', str_replace("\r", "", str_replace("\n", "", $html)));
    preg_match_all('|<img src="(https://www.sup.org.*?)".*?/>|', $html, $image_matches);
    foreach ($image_matches[0] as $key => $image_tag) {
      $image_url = $image_matches[1][$key];
      $media = $this->importImageMedia($image_url, 'public://media/image');
      $html = str_replace($image_tag, '<drupal-media data-entity-type="media" data-entity-uuid="' . $media->uuid() . '">&nbsp;</drupal-media>', $html);
    }
    return $html;
  }

  protected function importImageMedia($image_url, $directory, $media_type = 'image') {
    $ms = $this->entityTypeManager->getStorage('media');
    $filename = basename($image_url);
    $file = $this->downloadFile($image_url, $directory);

    $field_name = $media_type == 'image' ? 'field_media_image' : 'su_gallery_image';
    $media = $ms->create([
      'bundle' => $media_type,
      'name' => $filename,
      $field_name => ['target_id' => $file->id(), 'alt' => ''],
    ]);
    $media->save();
    return $media;
  }

  protected function importFileMedia($file_url, $directory) {
    $ms = $this->entityTypeManager->getStorage('media');
    $file = $this->downloadFile($file_url, $directory);
    $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);

    $media = $ms->create([
      'bundle' => 'file',
      'name' => ucFirst(trim(str_replace('_', ' ', str_replace(".$file_extension", '', basename($file_url))))),
      'field_media_file' => ['target_id' => $file->id()],
    ]);
    $media->save();
    return $media;
  }

  protected function downloadFile(string $file_url, string $directory) {
    $fs = $this->entityTypeManager->getStorage('file');
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $filename = basename($file_url);
    $final_destination = "$directory/$filename";

    $destination = @fopen($final_destination, 'w');
    $options = [
      'sink' => $destination,
      'verify' => FALSE,
    ];
    $this->client->get($file_url, $options);
    if (is_resource($destination)) {
      fclose($destination);
    }

    $file = $fs->create([
      'uri' => $final_destination,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();
    return $file;
  }

}
