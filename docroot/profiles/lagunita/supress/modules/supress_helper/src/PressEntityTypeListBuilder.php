<?php

declare(strict_types=1);

namespace Drupal\supress_helper;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of press entity type entities.
 *
 * @see \Drupal\supress_helper\Entity\PressEntityType
 */
final class PressEntityTypeListBuilder extends ConfigEntityListBuilder {

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
      'No press entity types available. <a href=":link">Add press entity type</a>.',
      [':link' => Url::fromRoute('entity.press_type.add_form')->toString()],
    );

    return $build;
  }

}
