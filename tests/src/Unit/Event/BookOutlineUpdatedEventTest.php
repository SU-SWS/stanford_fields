<?php

namespace Drupal\Tests\stanford_fields\Unit\Event;

use Drupal\node\NodeInterface;
use Drupal\stanford_fields\Event\BookOutlineUpdatedEvent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\stanford_fields\Event\BookOutlineUpdatedEvent
 */
class BookOutlineUpdatedEventTest extends UnitTestCase {

  /**
   * Test event methods.
   */
  public function testEvent() {
    $node = $this->createMock(NodeInterface::class);
    $node->book = ['bid' => '123'];
    $node->method('id')->willReturn(321);
    $event = new BookOutlineUpdatedEvent($node);

    $this->assertEquals(123, $event->getUpdatedBookId());
    $this->assertEquals(321, $event->getSavedNode()->id());
  }

}
