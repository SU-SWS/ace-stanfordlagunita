<?php

namespace Drupal\sul_helper\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Config overrides for the library site.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Text formats that should have the image credit filter.
   */
  const IMAGE_CREDIT_FORMATS = ['filter.format.stanford_html'];

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    foreach (array_intersect($names, self::IMAGE_CREDIT_FORMATS) as $name) {
      $overrides[$name]['filters']['sul_image_credit'] = [
        'id' => 'sul_image_credit',
        'provider' => 'sul_helper',
        'status' => TRUE,
        'weight' => -48,
        'settings' => [],
      ];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SulHelperConfigOverrides';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore Nothing to test.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
