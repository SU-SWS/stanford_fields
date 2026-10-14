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
 *   id = "ViewsReferenceSort"
 * )
 */
class ViewsReferenceSort extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A reference to an embedded view'),
      'fields' => fn() => [
        'field' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The machine name of the view.'),
        ],
        'direction' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The machine name of the display.'),
        ],
      ],
    ]);

    return $types;
  }

}
