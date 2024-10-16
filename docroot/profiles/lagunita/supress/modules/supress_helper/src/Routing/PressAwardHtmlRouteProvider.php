<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides HTML routes for entities with administrative pages.
 */
final class PressAwardHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type): ?Route {
    return $this->getEditFormRoute($entity_type);
  }

}
