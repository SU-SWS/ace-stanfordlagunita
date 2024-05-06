<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "sup_award",
 *   base_fields = {
 *      "name" = {
 *        "field_type" = "entity_label",
 *      },
 *   }
 * )
 */
class PressAward extends GraphQLComposeEntityTypeBase {

}
