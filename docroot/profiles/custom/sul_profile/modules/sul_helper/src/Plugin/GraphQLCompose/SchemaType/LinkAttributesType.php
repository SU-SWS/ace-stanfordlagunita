<?php

declare(strict_types=1);

namespace Drupal\sul_helper\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "LinkAttributes"
 * )
 */
class LinkAttributesType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Link Attributes.'),
      'fields' => fn() => [
        'ariaLabel' => [
          'type' => Type::string(),
          'description' => (string) $this->t('Aria Label'),
        ],
        'ariaLabelledBy' => [
          'type' => Type::string(),
          'description' => (string) $this->t('Aria Labelled By'),
        ],
      ],
    ]);

    return $types;
  }

}
