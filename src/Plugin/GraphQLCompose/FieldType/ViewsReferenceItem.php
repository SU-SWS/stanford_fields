<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose_views\Plugin\views\display\GraphQL;
use Drupal\views\Views;

/**
 * {@inheritDoc}
 *
 * @codeCoverageIgnore Unclear how to test for this.
 *
 * @GraphQLComposeFieldType(
 *   id = "viewsreference",
 *   type_sdl = "ViewsReferenceType",
 * )
 */
class ViewsReferenceItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    if (empty($item->target_id) || empty($item->display_id)) {
      return NULL;
    }

    $view = Views::getView($item->target_id);
    $view->setDisplay($item->display_id);

    if (!$view || !$view->access($item->display_id)) {
      return NULL;
    }

    $context->addCacheableDependency($view);

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();
    $options = unserialize($item->data) ?? [];
    $args = empty($options['argument']) ? NULL : explode('/', $options['argument'] ?? '');
    $size = is_numeric($options['limit']) ? (int) $options['limit'] : NULL;
    $offset = is_numeric($options['offset']) ? (int) $options['offset'] : NULL;

    return [
      'view' => $item->target_id,
      'display' => $item->display_id,
      'contextualFilter' => $args,
      'pageSize' => $size,
      'sort' => isset($options['sort']['field']) ? $options['sort'] : NULL,
      'pager' => $options['pager'] ?? NULL,
      'offset' => $offset,
      'query' => $display instanceof GraphQL ? $display->getGraphQlQueryName() : NULL,
    ];
  }

}
