<?php

declare(strict_types=1);

namespace Drupal\sul_helper\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType\LinkType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 *
 */
class SulLinkType extends LinkType {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A link.'),
      'fields' => fn() => [
        'title' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The title of the link.'),
        ],
        'url' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The URL of the link.'),
        ],
        'internal' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether the link is internal to this website.'),
        ],
        'attributes' => [
          'type' => static::type('LinkAttributes'),
          'description' => (string) $this->t('Link attributes'),
        ],
      ],
    ]);

    return $types;
  }

}