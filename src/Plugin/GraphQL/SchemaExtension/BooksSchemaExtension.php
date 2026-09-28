<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQL\SchemaExtension;

use Drupal\book\BookHelperTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\SchemaExtension;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\ResolverOnlySchemaExtensionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout Schema Extension.
 *
 * @codeCoverageIgnore
 */
#[SchemaExtension(
  id: "stanford_fields_books",
  name: new TranslatableMarkup("Stanford Decoupled Books"),
  schema: "graphql_compose",
  description: new TranslatableMarkup("Layout entities"),
)]
class BooksSchemaExtension extends ResolverOnlySchemaExtensionPluginBase {

  use BookHelperTrait;

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
    $book_types = $this->getBookContentTypes($book_settings->get('allowed_types'));
    $node_plugin = $this->gqlEntityTypeManager->getPluginInstance('node');

    foreach ($book_types as $node_type) {
      // Only enabled bundles are returned for the current server.
      if ($bundle = $node_plugin?->getBundle($node_type)) {
        $registry->addFieldResolver($bundle->getTypeSdl(), 'book', $builder->compose(
          $builder->produce('book')
            ->map('entity', $builder->fromParent())
            ->map('field', $builder->fromValue('book')),
        ));
      }
    }
  }

}
