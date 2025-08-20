<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "redirect",
 *   base_fields = {
 *      "redirect_source" = {
 *        "field_type" = "redirect_source",
 *        "required" = TRUE,
 *      },
 *      "redirect_redirect" = {
 *        "field_type" = "link",
 *        "required" = TRUE
 *      },
 *      "status_code" = {
 *        "field_type" = "integer",
 *        "required" = TRUE
 *      }
 *    }
 * )
 */
class Redirect extends GraphQLComposeEntityTypeBase {

}
