<?php

namespace Drupal\sul_helper\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add URL aliases to links.
 *
 * @ResourceFieldEnhancer(
 *   id = "base64_image",
 *   label = @Translation("Base64 Image"),
 *   description = @Translation("Convert the image into a base64 string.")
 * )
 */
class Base64Image extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $resource_field_info) {
    $form = parent::getSettingsForm($resource_field_info);
    $config = $this->getConfiguration();
    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#options' => image_style_options(FALSE),
      '#default_value' => $config['enhancer']['settings']['style'] ?? 'thumbnail',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uri' => $data['value']]);
    $config = $this->getConfiguration();

    if (count($files) == 1) {
      $file = reset($files);

      if (!str_starts_with($file->getMimeType(), 'image/')) {
        return $data;
      }

      /** @var \Drupal\image\ImageStyleInterface $image_style */
      $image_style = $this->entityTypeManager->getStorage('image_style')
        ->load($config['style']);

      $destination = $image_style->buildUri($file->getFileUri());
      $image_style->createDerivative($file->getFileUri(), $destination);

      if (file_exists($destination)) {
        $data['base64'] = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($destination));
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {}

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'value' => ['type' => 'string'],
        'url' => ['type' => 'string'],
        'base64' => ['type' => 'string'],
      ],
    ];
  }

}
