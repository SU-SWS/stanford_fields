<?php

namespace Drupal\stanford_fields\Event;

use Drupal\node\NodeInterface;
use Drupal\Component\EventDispatcher\Event;

class BookOutlineUpdatedEvent extends Event {

  const OUTLINE_UPDATED = 'book.outline_updated';

  protected $node;

  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

  public function getUpdatedBookId() {
    return $this->node->book['bid'];
  }

}
