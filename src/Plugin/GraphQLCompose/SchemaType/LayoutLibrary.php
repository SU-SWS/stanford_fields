<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @codeCoverageIgnore Unclear how to test for this.
 *
 * @GraphQLComposeSchemaType(
 *   id = "LayoutLibrary"
 * )
 */
class LayoutLibrary extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Layout Library entity.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('Machine name of the layout definition.'),
        ],
        'label' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('Human readable name of the layout definition.'),
        ],
      ],
    ]);

    return $types;
  }

}
