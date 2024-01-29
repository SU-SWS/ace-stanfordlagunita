<?php

namespace Drupal\sul_profile\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class InstallTask.
 *
 * @Annotation
 */
class InstallTask extends Plugin {

  /**
   * Array of tasks that must run before the current plugin.
   *
   * @var array
   */
  protected $dependencies = [];

}
