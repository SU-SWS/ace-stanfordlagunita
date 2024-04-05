<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a press award entity type.
 */
interface PressAwardInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
