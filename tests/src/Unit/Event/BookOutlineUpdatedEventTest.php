<?php

namespace Drupal\Tests\stanford_fields\Unit\Event;

use Drupal\node\Entity\Node;
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
    $node = $this->getMockBuilder(Node::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['id', '__isset', '__get'])
      ->getMock();
    $node->method('__isset')->willReturnCallback(fn($name) => $name == 'book');
    $node->method('__get')->willReturnCallback(fn($name) => $name == 'book' ? ['bid' => '123'] : NULL);
    $node->method('id')->willReturn(321);
    $event = new BookOutlineUpdatedEvent($node);

    $this->assertEquals(123, $event->getUpdatedBookId());
    $this->assertEquals(321, $event->getSavedNode()->id());
  }

}
