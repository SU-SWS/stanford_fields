<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Attribute\EntityType;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 */
#[EntityType(
  id: "config_pages",
)]
class ConfigPage extends GraphQLComposeEntityTypeBase {

}
