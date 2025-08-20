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
 *   id = "NameType"
 * )
 */
class NameType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('name')) {
      return $types;
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Smart Date data.'),
      'fields' => fn() => [
        'title' => [
          'type' => Type::string(),
          'description' => 'Title',
        ],
        'given' => [
          'type' => Type::string(),
          'description' => 'Given',
        ],
        'middle' => [
          'type' => Type::string(),
          'description' => 'Middle name(s)',
        ],
        'family' => [
          'type' => Type::string(),
          'description' => 'Family',
        ],
        'generational' => [
          'type' => Type::string(),
          'description' => 'Generational',
        ],
        'credentials' => [
          'type' => Type::string(),
          'description' => 'Credentials',
        ],
      ],
    ]);

    return $types;
  }

}
