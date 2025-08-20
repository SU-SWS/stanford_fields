<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "citation",
 *   prefix = "Citation",
 *   base_fields = {
 *      "created" = {},
 *      "changed" = {},
 *      "title" = {
 *        "field_type" = "entity_label",
 *      }
 *    },
 * )
 */
class Citation extends GraphQLComposeEntityTypeBase {

}
