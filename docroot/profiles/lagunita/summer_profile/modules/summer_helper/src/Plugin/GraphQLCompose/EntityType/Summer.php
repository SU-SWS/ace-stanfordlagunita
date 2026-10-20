<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Attribute\EntityType;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 */
#[EntityType(
  id: 'summer_entity',
  prefix: 'Summer',
  base_fields: [
    'label' => [
      'field_type' => 'entity_label',
    ],
  ],
)]
class Summer extends GraphQLComposeEntityTypeBase {

}
