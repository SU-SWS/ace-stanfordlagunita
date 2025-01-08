<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "summer_entity",
 *   prefix = "Summer",
 *   base_fields = {
 *     "label" = {
 *       "field_type" = "entity_label",
 *     },
 *   },
 * )
 */
class Summer extends GraphQLComposeEntityTypeBase {

}
