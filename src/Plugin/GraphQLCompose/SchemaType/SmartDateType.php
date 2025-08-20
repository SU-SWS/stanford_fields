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
 *   id = "SmartDateType"
 * )
 */
class SmartDateType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('smart_date')) {
      return $types;
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Smart Date data.'),
      'fields' => fn() => [
        'value' => [
          'type' => Type::nonNull(static::type('Timestamp')),
          'description' => 'Start timestamp value',
        ],
        'end_value' => [
          'type' => Type::nonNull(static::type('Timestamp')),
          'description' => 'End timestamp value',
        ],
        'duration' => [
          'type' => Type::int(),
          'description' => 'Duration, in minutes',
        ],
        'rrule' => [
          'type' => Type::int(),
          'description' => 'RRule ID',
        ],
        'rrule_index' => [
          'type' => Type::int(),
          'description' => 'RRule Index',
        ],
        'timezone' => [
          'type' => Type::string(),
          'description' => 'Timezone',
        ],
      ],
    ]);

    return $types;
  }

}
