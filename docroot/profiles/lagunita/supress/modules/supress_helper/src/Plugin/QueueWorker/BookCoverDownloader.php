<?php

namespace Drupal\supress_helper\Plugin\QueueWorker;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cron queue worker for filemaker cover downloading.
 *
 * @QueueWorker(
 *   id = "press_cover_downloader",
 *   title = @Translation("Book cover downloader"),
 *   cron = {"time" = 60}
 * )
 */
class BookCoverDownloader extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  const FILE_DIRECTORY = 'public://media/covers';

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
      $container->get('config_pages.loader')
    );
  }

  /**
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
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   Config page service to load values.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ClientInterface $client, protected FileSystemInterface $fileSystem, protected EntityTypeManagerInterface $entityTypeManager, protected ConfigPagesLoaderServiceInterface $configPagesLoader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    $client_id = $this->configPagesLoader->getValue('stanford_basic_site_settings', 'sup_filemaker_user', 0, 'value');
    $client_secret = $this->configPagesLoader->getValue('stanford_basic_site_settings', 'sup_filemaker_pass', 0, 'value');

    if (!$client_id || !$client_secret) {
      return;
    }

    // Fetch the OAuth-like token form the API.
    $token_response = (string) $this->client->request('POST', 'https://memento.stanford.edu/fmi/data/v2/databases/Web/sessions', [
      'verify' => FALSE,
      'headers' => ['Content-Type' => 'application/json'],
      'auth' => [$client_id, $client_secret],
    ])->getBody();
    $token = json_decode($token_response, TRUE);

    // Use the token to fetch the cover record for this one item.
    try {
      $covers_response = (string) $this->client->request('GET', "https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Covers/records/$data", [
        'verify' => FALSE,
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $token['response']['token'],
        ],
        'timeout' => 60,
      ])->getBody();
    }
    catch (\Exception $e) {
      // If the record no longer exists in the API or there was some issue with
      // the request, just bail. The queue will recreate the item if it still
      // exists from the API.
      return;
    }

    $covers = json_decode($covers_response, TRUE);

    // Prep the file system directory.
    $public_path = self::FILE_DIRECTORY;
    $this->fileSystem->prepareDirectory($public_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Since we're only fetching 1 record, the response data will only contain 1 item.
    $cover = reset($covers['response']['data']);

    $image_url = $cover['fieldData']['image'];

    // The extension is almost always a jpg, but let's grab the extension
    // from the url, just in case it might be jpeg or png.
    preg_match('/.*(\.\w+)\?/', $image_url, $extension_match);
    $extension = $extension_match[1] ?? '.jpg';
    $work_id = $cover['fieldData']['work_id_number'];

    $media_id = $this->downloadImage($image_url, "$public_path/$work_id$extension", $work_id);

    // Call the API to update the flag in the system.
    $this->client->request('GET', "https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Covers/scripts/ClearFlags?script.param=$work_id", [
      'verify' => FALSE,
      'headers' => ['Content-Type' => 'application/json'],
      'auth' => [$client_id, $client_secret],
    ]);
    return $media_id;
  }

  /**
   * Download the image from the FileMaker system.
   *
   * @param string $image_url
   *   FileMaker API URL.
   * @param string $destination
   *   Public directory path, starts with public://.
   * @param int $work_id_number
   *   API Work ID Number that matches the book record.
   *
   * @return int
   *   Media id.
   */
  protected function downloadImage(string $image_url, string $destination, int $work_id_number): int {
    // Copy the file to a temporary location first. Then we'll move it to the
    // actual destination, while preserving any existing files.
    $temp_destination = $this->fileSystem->tempnam(self::FILE_DIRECTORY, basename($destination));
    $sink_path = $this->fileSystem->realpath($temp_destination);

    $this->client->request('GET', $image_url, [
      'verify' => FALSE,
      'sink' => $sink_path,
      'cookies' => new CookieJar(),
      'timeout' => 60,
    ]);

    $new_file_path = $this->fileSystem->move($sink_path, $destination);

    $file_storage = $this->entityTypeManager->getStorage('file');
    $media_storage = $this->entityTypeManager->getStorage('media');

    /** @var \Drupal\file\FileInterface $new_file */
    $new_file = $file_storage->create(['uri' => $new_file_path]);
    $new_file->setPermanent();
    $new_file->save();

    $existing_media = $media_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('sup_book_work_id', $work_id_number)
      ->execute();

    // Update the media item with the new file id.
    if ($existing_media) {
      /** @var \Drupal\media\MediaInterface $media */
      $media = $media_storage->load(reset($existing_media));
      $media->set('field_media_image', [
        'alt' => '',
        'target_id' => $new_file->id(),
      ])->save();
      return $media->id();
    }

    $media = $media_storage->create([
      'bundle' => 'image',
      'label' => basename($destination),
      'sup_book_work_id' => $work_id_number,
      'field_media_image' => [
        'alt' => '',
        'target_id' => $new_file->id(),
      ],
    ]);
    $media->save();
    return $media->id();
  }

}