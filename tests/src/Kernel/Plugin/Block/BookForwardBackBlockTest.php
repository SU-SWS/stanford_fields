<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Block;

use Drupal\node\Entity\Node;
use Drupal\stanford_fields\Plugin\Block\BookForwardBackBlock;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;

/**
 * Test the book block.
 *
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Block\BookForwardBackBlock
 */
class BookForwardBackBlockTest extends StanfordFieldKernelTestBase {
  /**
   * Book node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $book;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('module_installer')->install(['book']);

    \Drupal::configFactory()->getEditable('book.settings')
      ->set('allowed_types', ['page'])
      ->set('child_type', 'page')
      ->save();

    $this->book = Node::create(['type' => 'page', 'title' => 'Book Foo']);
    $this->book->setPublished();
    $this->book->book = [
      'nid' => NULL,
      'bid' => 'new',
      'pid' => -1,
      'parent_depth_limit' => '9',
    ];
    $this->book->save();
  }

  public function testBlock(){
    $block = \Drupal::service('plugin.manager.block')->createInstance('book_forward_back');
    $this->assertInstanceOf(BookForwardBackBlock::class, $block);

    $this->assertEmpty($block->build());


    $block->setContextValue('node', $this->book);
    $this->assertEquals('book_navigation', $block->build()['#theme']);
  }

}
