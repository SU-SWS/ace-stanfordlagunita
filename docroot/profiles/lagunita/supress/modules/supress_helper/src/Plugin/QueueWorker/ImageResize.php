<?php

namespace Drupal\supress_helper\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cron queue worker for Resizing images
 *
 * @codeCoverageIgnore
 *
 * @QueueWorker(
 *   id = "press_image_resize",
 *   title = @Translation("Image Resize"),
 *   cron = {"time" = 60}
 * )
 */
class ImageResize extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
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
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Drupal file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected FileSystemInterface $fileSystem, protected EntityTypeManagerInterface $entityTypeManager, protected LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    $image_style = $this->entityTypeManager->getStorage('image_style')->load("breakpoint_2xl_1x");
    $file = $this->entityTypeManager->getStorage("file")->load($data);
    $temp = "temporary://" . $file->label();
    $success = $image_style->createDerivative($file->getFileUri(), $temp);
    if (!$success) {
      $this->logger->get('supress')->error('Unable to generate image derivative for file @uri', ['@uri' => $file->getFileUri()]);
      return;
    }
    $this->fileSystem->move($temp, $file->getFileUri(), FileExists::Replace);
    $file->save();
  }

}
