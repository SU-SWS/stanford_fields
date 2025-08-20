<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritDoc}
 *
 * @codeCoverageIgnore Unclear how to test for this.
 *
 * @GraphQLComposeFieldType(
 *   id = "smartdate",
 *   type_sdl = "SmartDateType",
 * )
 */
class SmartDateItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    return [
      'value' => $item->get('value')->getValue(),
      'end_value' => $item->get('end_value')->getValue(),
      'duration' => $item->get('duration')->getValue(),
      'rrule' => $item->get('rrule')->getValue(),
      'rrule_index' => $item->get('rrule_index')->getValue(),
      'timezone' => $item->get('timezone')->getValue(),
    ];
  }

}
