<?php

declare(strict_types=1);

namespace Drupal\summer_helper;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining a summer entity type.
 */
interface SummerInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

}
