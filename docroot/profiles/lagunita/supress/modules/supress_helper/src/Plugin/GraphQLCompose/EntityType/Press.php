<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "press",
 *   prefix = "Press",
 *   base_fields = {
 *     "work_id" = {},
 *     "title" = {
 *       "field_type" = "entity_label",
 *     },
 *   },
 * )
 */
class Press extends GraphQLComposeEntityTypeBase {

}
