<?php

namespace Drupal\stanford_fields\Event;

use Drupal\node\NodeInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event triggered when the book outline service is complete.
 */
class BookOutlineUpdatedEvent extends Event {

  const OUTLINE_UPDATED = 'book.outline_updated';

  /**
   * Node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Event constructor.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

  /**
   * Get the node that was saved to trigger the event.
   *
   * @return \Drupal\node\NodeInterface
   *   Node entity.
   */
  public function getSavedNode(): NodeInterface {
    return $this->node;
  }

  /**
   * Get the id of the book node.
   *
   * @return int|null
   *   Book entity id.
   */
  public function getUpdatedBookId(): ?int {
    return $this->node->book['bid'] ?? NULL;
  }

}
