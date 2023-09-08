<?php

namespace Drupal\Tests\stanford_fields\Kernel\Service;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Decorated book manager service tests.
 *
 * @coversDefaultClass \Drupal\stanford_fields\Service\StanfordFieldsBookManager
 */
class StanfordFieldBookManagerTest extends StanfordFieldKernelTestBase {

  /**
   * Book node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $book;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
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

  /**
   * Test the method that adds the form elements.
   */
  public function testAddFormElements() {
    /** @var \Drupal\book\BookManagerInterface $manager */
    $manager = \Drupal::service('book.manager');

    $form = [];
    $form_state = new FormState();

    $account = $this->createMock(AccountProxyInterface::class);

    $node = $this->createMock(NodeInterface::class);
    $altered_form = $manager->addFormElements($form, $form_state, $node, $account);
    $this->assertEmpty($altered_form);

    $node = Node::create(['type' => 'page', 'title' => 'foobar']);
    $node->book = $manager->getLinkDefaults('new');
    $node->book['parent_depth_limit'] = 9;
    $node->setPublished();
    $node->save();

    $altered_form = $manager->addFormElements($form, $form_state, $node, $account);
    $this->assertFalse($altered_form['book']['weight']['#access']);

    $form_state->setValue('book', [
      'nid' => $node->id(),
      'bid' => $this->book->id(),
      'original_bid' => -1,
      'pid' => $this->book->id(),
      'has_children' => 0,
      'weight' => [$node->id() => ['weight' => 10]],
      'parent_depth_limit' => 9,
    ]);
    $altered_form = $manager->addFormElements($form, $form_state, $node, $account);
    $this->assertTrue($altered_form['book']['weight']['#access']);
    $this->assertNotEmpty(Element::children($altered_form['book']['weight']));

    $this->assertArrayHasKey($this->book->id() . ':new', $altered_form['book']['weight']);

    $sibling = Node::create(['type' => 'page', 'title' => 'Book Foo']);
    $sibling->book = [
      'nid' => NULL,
      'bid' => $this->book->id(),
      'original_bid' => 0,
      'pid' => $this->book->id(),
      'weight' => 50,
      'parent_depth_limit' => '9',
      'has_children' => 0,
    ];
    $sibling->setPublished();
    $sibling->save();

    $altered_form = $manager->addFormElements($form, $form_state, $node, $account);
    $this->assertArrayHasKey($this->book->id() . ':' . $sibling->id(), $altered_form['book']['weight']);
    $this->assertNotEmpty(Element::children($altered_form['book']['weight']));
  }

  public function testUpdateOutline() {
    $sibling = Node::create(['type' => 'page', 'title' => 'Book Foo']);
    $sibling->book = [
      'nid' => NULL,
      'bid' => $this->book->id(),
      'original_bid' => 0,
      'pid' => $this->book->id(),
      'weight' => 50,
      'parent_depth_limit' => '9',
      'has_children' => 0,
    ];
    $sibling->setPublished();
    $sibling->save();

    $node = Node::create(['type' => 'page', 'title' => 'Book Foo']);
    $node->book = [
      'nid' => NULL,
      'bid' => $this->book->id(),
      'original_bid' => 0,
      'pid' => $this->book->id(),
      'weight' => 23,
      'parent_depth_limit' => '9',
      'has_children' => 0,
    ];
    $node->setPublished();
    $node->save();
    $node = Node::load($node->id());
    $this->assertEquals(23, $node->book['weight']);

    $node->book['weight'] = [
      'foo:' . $sibling->id() => ['weight' => 12],
      'foo:' . $node->id() => ['weight' => 24],
    ];
    $node->save();
    $node = Node::load($node->id());
    $this->assertEquals(24, $node->book['weight']);
  }

  public function testOutlineAccess() {
    // Create user 1 first.
    User::create(['name' => $this->randomMachineName()])->save();

    $account = User::create(['name' => $this->randomMachineName()]);

    $account->save();
    $account = User::load($account->id());
    $this->container->get('current_user')->setAccount($account);

    $access = Url::fromRoute('entity.node.book_outline_form', ['node' => 999])
      ->access($account);
    $this->assertFalse($access);

    $access = Url::fromRoute('entity.node.book_outline_form', ['node' => $this->book->id()])
      ->access($account);
    $this->assertFalse($access);

    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['administer book outlines']);
    $access = Url::fromRoute('entity.node.book_outline_form', ['node' => $this->book->id()])
      ->access($account);
    $this->assertTrue($access);

    \Drupal::configFactory()->getEditable('book.settings')
      ->set('allowed_types', ['foobar_page'])
      ->set('child_type', 'foobar_page')
      ->save();

    $access = Url::fromRoute('entity.node.book_outline_form', ['node' => $this->book->id()])
      ->access($account);
    $this->assertFalse($access);
  }

}
