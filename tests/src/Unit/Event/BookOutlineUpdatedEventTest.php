<?php

namespace Drupal\Tests\stanford_fields\Unit\Event;

use Drupal\book\Entity\Node\Book;
use Drupal\node\NodeInterface;
use Drupal\stanford_fields\Event\BookOutlineUpdatedEvent;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the book outline updated event.
 */
#[Group('stanford_fields')]
class BookOutlineUpdatedEventTest extends UnitTestCase {

  /**
   * Test event methods.
   */
  public function testEvent() {
    $node = $this->getMockBuilder(Book::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['id', 'getBook'])
      ->getMock();
    $node->method('getBook')->willReturn(['bid' => '123']);
    $node->method('id')->willReturn(321);
    $event = new BookOutlineUpdatedEvent($node);

    $this->assertEquals(123, $event->getUpdatedBookId());
    $this->assertEquals(321, $event->getSavedNode()->id());
  }

  /**
   * Nodes that can't be in a book have no book id.
   */
  public function testNonBookNode() {
    $event = new BookOutlineUpdatedEvent($this->createMock(NodeInterface::class));
    $this->assertNull($event->getUpdatedBookId());
  }

}
