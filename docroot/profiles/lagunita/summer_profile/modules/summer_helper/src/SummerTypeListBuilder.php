<?php

declare(strict_types=1);

namespace Drupal\summer_helper;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of summer entity type entities.
 *
 * @see \Drupal\summer_helper\Entity\SummerType
 */
final class SummerTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No summer entity types available. <a href=":link">Add summer entity type</a>.',
      [':link' => Url::fromRoute('entity.summer_entity_type.add_form')->toString()],
    );

    return $build;
  }

}
