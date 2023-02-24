<?php

namespace Drupal\sul_helper\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * SUL Config overrides.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritDoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (!$this->isProdEnv()) {
      foreach ($names as $config_name) {
        if (str_starts_with($config_name, 'next.next_site.')) {
          $overrides[$config_name]['revalidate_url'] = '';
        }
        if (str_starts_with($config_name, 'next.next_entity_type_config.')) {
          $overrides[$config_name]['revalidator'] = '';
        }
      }
    }
    return $overrides;
  }

  /**
   * Check if this is Acquia's prod environment.
   *
   * @return bool
   *   Is Acquia environment.
   */
  protected function isProdEnv() {
    $ah_env = $_ENV['AH_SITE_ENVIRONMENT'] ?? '';
    return $ah_env == 'prod' || preg_match('/^\d*live$/', $ah_env);
  }

  /**
   * {@inheritDoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheSuffix() {
    return 'SulHelperConfigOverride';
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
