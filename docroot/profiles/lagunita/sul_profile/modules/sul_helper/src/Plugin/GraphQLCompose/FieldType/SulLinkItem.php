<?php

declare(strict_types=1);

namespace Drupal\sul_helper\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\LinkItem;

/**
 *
 */
class SulLinkItem extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    $data = parent::resolveFieldItem($item, $context);
    /** @var \Drupal\Core\TypedData\Plugin\DataType\Map $item_options */
    $item_options = $item->get('options')->getValue();
    if ($item_options && isset($item_options['attributes']['aria-label'])) {
      $data['attributes']['ariaLabel'] = $item_options['attributes']['aria-label'];
    }
    return $data;
  }

}