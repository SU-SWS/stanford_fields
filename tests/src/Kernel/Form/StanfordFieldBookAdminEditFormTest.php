<?php

namespace Drupal\Tests\stanford_fields\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\Node;
use Drupal\stanford_fields\Form\StanfordFieldBookAdminEditForm;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;

/**
 * @coversDefaultClass \Drupal\stanford_fields\Form\StanfordFieldBookAdminEditForm
 */
class StanfordFieldBookAdminEditFormTest extends StanfordFieldKernelTestBase {

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
      'parent_depth_limit' => 9,
      'has_children' => 0,
      'weight' => 0,
    ];
    $this->book->save();
    $this->book = Node::load($this->book->id());

    $node = Node::create(['type' => 'page', 'title' => 'Foo']);
    $node->book = [
      'nid' => NULL,
      'bid' => $this->book->id(),
      'pid' => $this->book->id(),
      'parent_depth_limit' => 9,
      'has_children' => 0,
      'weight' => 0,
    ];
    $node->save();
  }

  public function testFormBuild() {
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = \Drupal::service('form_builder');
    $form_state = new FormState();
    $form_state->setBuildInfo(['args' => ['node' => $this->book]]);
    $form = $form_builder->buildForm(StanfordFieldBookAdminEditForm::class, $form_state);
    $this->assertNotEmpty($form);
    $children = Element::children($form['table']);
    $key = reset($children);
    $this->assertEquals('markup', $form['table'][$key]['title']['#type']);

    $form_builder->submitForm(StanfordFieldBookAdminEditForm::class, $form_state);
    $this->assertEquals('Foo', $form_state->getValue(['table', $key, 'title']));
  }

}
