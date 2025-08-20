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
 *   id = "FontawesomeIconType"
 * )
 */
class FontawesomeIconType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('fontawesome')) {
      return $types;
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('FontAwesome Icon.'),
      'fields' => fn() => [
        'iconName' => [
          'type' => Type::nonNull(Type::string()),
          'description' => 'Icon Name',
        ],
        'style' => [
          'type' => Type::nonNull(Type::string()),
          'description' => 'Icon Style',
        ],
      ],
    ]);

    return $types;
  }

}
