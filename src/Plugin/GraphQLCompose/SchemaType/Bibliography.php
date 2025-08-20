<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\ObjectType;
use function Symfony\Component\String\u;

/**
 * {@inheritDoc}
 *
 * @codeCoverageIgnore
 *
 * @GraphQLComposeSchemaType(
 *   id = "Bibliography"
 * )
 */
class Bibliography extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new CustomScalarType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Bibliography citation format markup.'),
    ]);

    return $types;
  }

  /**
   * {@inheritDoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();
    $citation_types = $this->entityTypeManager->getStorage('citation_type')
      ->loadMultiple();
    foreach (array_keys($citation_types) as $citation_type) {
      $citation_type = u($citation_type)
        ->camel()
        ->title()
        ->toString();

      $extensions[] = new ObjectType([
        'name' => 'Citation' . trim($citation_type, 's'),
        'fields' => fn() => [
          'apa' => static::type('Bibliography'),
          'chicago' => static::type('Bibliography'),
        ],
      ]);
    }

    return $extensions;
  }

}
