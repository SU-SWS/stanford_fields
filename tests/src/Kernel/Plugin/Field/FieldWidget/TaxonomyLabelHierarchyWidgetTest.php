<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;
use Drupal\user\Entity\User;
/**
 * Class TaxonomyLabelHierarchyWidgetTest.
 *
 * @group stanford_fields
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldWidget\TaxonomyLabelHierarchyWidget
 */
class TaxonomyLabelHierarchyWidgetTest extends StanfordFieldKernelTestBase {

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();

    Vocabulary::create(['vid' => 'tag_terms', 'label' => 'terms'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_terms',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => ['target_type' => 'taxonomy_term'],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_terms',
      'entity_type' => 'node',
      'bundle' => 'page',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['tag_terms' => 'tag_terms'],
        ],
      ],
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entity_form_display->setComponent('field_terms', [
      'type' => 'taxonomy_label_hierarchy',
    ])->removeComponent('created')->save();
  }

  /**
   * Test the entity form is displayed correctly.
   */
  public function testWidgetForm() {
    $parent_term = Term::create([
      'vid' => 'tag_terms',
      'name' => 'Parent',
      'parent' => [0],
    ]);
    $parent_term->save();

    $second_parent_term = Term::create([
      'vid' => 'tag_terms',
      'name' => 'Second Parent',
      'parent' => [0],
    ]);
    $second_parent_term->save();

    $foo = Term::create([
      'vid' => 'tag_terms',
      'name' => 'Foo',
      'parent' => [$parent_term->id()],
    ]);
    $foo->save();

    $bar = Term::create([
      'vid' => 'tag_terms',
      'name' => 'Bar',
      'parent' => [$parent_term->id()],
    ]);
    $bar->save();

    $baz = Term::create([
      'vid' => 'tag_terms',
      'name' => 'Baz',
      'parent' => [$second_parent_term->id()],
    ]);
    $baz->save();

    $author = User::create(['name' => 'foo']);
    $author->save();
    $node = Node::create(['type' => 'page', 'title' => 'foobar', 'uid' => $author->id()]);
    $node->save();

    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder */
    $form_builder = $this->container->get('entity.form_builder');
    $form = $form_builder->getForm($node);

    $widget_value = $form['field_terms']['widget'];

    $this->assertArrayHasKey($foo->id(), $widget_value['parent'][0]['target_id']['#options']);
    $this->assertArrayHasKey($bar->id(), $widget_value['parent'][0]['target_id']['#options']);
    $this->assertArrayNotHasKey($baz->id(), $widget_value['parent'][0]['target_id']['#options']);

    $this->assertArrayNotHasKey($foo->id(), $widget_value['second_parent'][0]['target_id']['#options']);
    $this->assertArrayNotHasKey($bar->id(), $widget_value['second_parent'][0]['target_id']['#options']);
    $this->assertArrayHasKey($baz->id(), $widget_value['second_parent'][0]['target_id']['#options']);
  }

}
