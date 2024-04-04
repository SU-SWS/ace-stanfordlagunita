<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the press award entity type.
 *
 * @codeCoverageIgnore
 */
final class PressAwardListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}
