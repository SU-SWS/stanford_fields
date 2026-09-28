<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Attribute\EntityType;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;
use Drupal\graphql_compose\Utility\ComposeConfig;

/**
 * {@inheritdoc}
 */
#[EntityType(
  id: "layout",
  type_sdl: "LayoutLibrary",
  base_fields: [
    "label" => [
      "field_type" => "entity_label",
      "required" => TRUE,
      "description" => "Human readable name of the layout definition.",
    ],
  ],
)]
class LayoutLibrary extends GraphQLComposeEntityTypeBase {

  /**
   * {@inheritdoc}
   *
   * Layouts are config entities with machine name IDs. The default entity_id
   * field resolver coerces IDs to integers, so resolve the raw ID instead.
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    parent::registerResolvers($registry, $builder);

    if (!ComposeConfig::get('settings.expose_entity_ids', FALSE)) {
      return;
    }

    foreach ($this->getBundles() as $bundle) {
      $registry->addFieldResolver(
        $bundle->getTypeSdl(),
        'id',
        $builder->produce('entity_id')->map('entity', $builder->fromParent()),
      );
    }
  }

}
