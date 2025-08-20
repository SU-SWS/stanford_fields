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
 *   id = "ColorFieldType"
 * )
 */
class ColorFieldType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('color_field')) {
      return $types;
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Color Field.'),
      'fields' => fn() => [
        'color' => [
          'type' => Type::nonNull(Type::string()),
          'description' => 'Color Hex',
        ],
        'opacity' => [
          'type' => Type::float(),
          'description' => 'Opacity',
        ],
      ],
    ]);

    return $types;
  }

}
