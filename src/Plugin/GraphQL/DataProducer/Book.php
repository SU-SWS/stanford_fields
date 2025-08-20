<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQL\DataProducer;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Produces a book menu tree from an entity.
 *
 * @codeCoverageIgnore
 *
 * @DataProducer(
 *   id = "book",
 *   name = @Translation("Book Tree"),
 *   description = @Translation("Book tree data from the give node."),
 *   produces = @ContextDefinition("mixed",
 *     label = @Translation("FieldItemListInterface"),
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Parent entity"),
 *     ),
 *   },
 * )
 */
class Book extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('book.manager'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected BookManagerInterface $bookManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Finds the requested field on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that contains the field.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The field context.
   */
  public function resolve(EntityInterface $entity, FieldContext $context): ?array {
    if ($entity instanceof NodeInterface && isset($entity->book['bid'])) {
      $book_tree = $this->bookManager->bookTreeAllData((int) $entity->book['bid']);
      return $this->buildBookTreeData(reset($book_tree));
    }

    return NULL;
  }

  /**
   * Massage the book tree data to provide the useful structure.
   *
   * @param array $tree
   *   Book menu tree data.
   *
   * @return array
   *   Modified tree data to reduce structure complexity.
   */
  protected function buildBookTreeData(array $tree) {
    $link = $tree['link'];
    $url = Url::fromRoute('entity.node.canonical', ['node' => $link['nid']]);
    $child_items = [];

    foreach ($tree['below'] as $below_tree) {
      $child_items[] = self::buildBookTreeData($below_tree);
    }

    return [
      'id' => 'book-' . $link['bid'] . '-' . $link['nid'],
      'title' => $link['title'],
      'url' => $url->toString(TRUE)->getGeneratedUrl(),
      'children' => $child_items,
      'description' => '',
      'langcode' => [],
      'internal' => TRUE,
      'expanded' => TRUE,
      'attributes' => [],
      'route' => NULL,
    ];
  }

}
