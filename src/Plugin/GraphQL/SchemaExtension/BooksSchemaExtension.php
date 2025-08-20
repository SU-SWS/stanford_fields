<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\ResolverOnlySchemaExtensionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Symfony\Component\String\u;

/**
 * Layout Schema Extension.
 *
 * @codeCoverageIgnore
 *
 * @SchemaExtension(
 *   id = "stanford_fields_books",
 *   name = "Stanford Decoupled Books",
 *   description = @Translation("Layout entities"),
 *   schema = "graphql_compose",
 * )
 */
class BooksSchemaExtension extends ResolverOnlySchemaExtensionPluginBase {

  /**
   * Book manager service.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->bookManager = $container->get('book.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $book_settings = $this->configFactory->get('book.settings');
    $graphql_compose = $this->configFactory->get('graphql_compose.settings');
    $book_types = $book_settings->get('allowed_types') ?: [];

    foreach ($book_types as $node_type) {
      $node_enabled = $graphql_compose->get("entity_config.node.$node_type.enabled");

      if ($node_enabled) {
        $node_type = u($node_type)
          ->camel()
          ->title()
          ->toString();

        $registry->addFieldResolver('Node' . $node_type, 'book', $builder->compose(
          $builder->produce('book')
            ->map('entity', $builder->fromParent())
            ->map('field', $builder->fromValue('book')),
        ));
      }
    }
  }

}
