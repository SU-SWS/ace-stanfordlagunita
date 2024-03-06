<?php

namespace Drupal\sul_helper;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;

/**
 * Interface for SUL Service helper.
 */
interface SulServiceInterface {

  /**
   * Get a lib guides on the service.
   *
   * @return array
   *   Array of guide array information.
   */
  public function getLibGuides(): array;

  /**
   * Get the list of active libcal user accounts.
   *
   * @return array
   *   Array of user details.
   */
  public function getLibCalUsers(): array;

}
