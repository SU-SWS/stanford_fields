<?php

namespace Drupal\stanford_fields\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Book navigation' block.
 */
#[Block(
  id: "book_forward_back",
  admin_label: new TranslatableMarkup("Book Forward & Back"),
  category: new TranslatableMarkup("Book"),
  context_definitions: [
    'node' => new EntityContextDefinition(data_type: 'entity:node', label: new TranslatableMarkup("Node"), required: FALSE),
  ]
)]
class BookForwardBackBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   *
   * @see book_node_view()
   */
  public function build() {
    $node = $this->getContextValue('node');

    if ($node && !empty($node->book['bid']) && empty($node->in_preview)) {
      $book_node = $this->entityTypeManager->getStorage('node')
        ->load($node->book['bid']);

      if (!$book_node->access()) {
        return [];
      }

      return [
        '#theme' => 'book_navigation',
        '#book_link' => $node->book,
        '#weight' => 100,
        '#cache' => ['tags' => $node->getEntityType()->getListCacheTags()],
      ];
    }
    return [];
  }

}
