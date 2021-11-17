<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Class LocalistUrlWidgetTest.
 *
 * @group
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldWidget\LocalistUrlWidget
 */
class LocalistUrlWidgetTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'path_alias',
    'node',
    'user',
    'link',
    'stanford_fields',
    'field',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('field_config');
    $this->installConfig(['system', 'field', 'link']);

    NodeType::create(['type' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'su_localist_url',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => -1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'su_localist_url',
      'entity_type' => 'node',
      'bundle' => 'page',
    ]);
    $field->save();

  }

  /**
   * Test the entity form is displayed correctly.
   */
  public function testWidgetForm() {
    $node = Node::create([
      'type' => 'page',
    ]);
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,

    ]);
    $entity_form_display->setComponent('su_localist_url', [
      'type' => 'localist_url',
      'settings' => [
        'base_url' => 'https://stanford.enterprise.localist.com',
      ],
    ])
      ->removeComponent('created')
      ->save();

    $node->set('su_localist_url', [
      [
        'uri' => 'https://stanford.enterprise.localist.com/api/2/events?group_id=37955145294460&days=365',
        'title' => '',
        'options' => '',
      ],
    ]);

    $form = [];
    $form_state = new FormState();

    $entity_form_display->buildForm($node, $form, $form_state);

    $widget_value = $form['su_localist_url']['widget'];

    $this->assertIsArray($widget_value[0]);
    $this->assertEquals('https://stanford.enterprise.localist.com/api/2/events?group_id=37955145294460&days=365', $widget_value[0]['uri']['#default_value']);
    $this->assertEquals('details', $widget_value[0]['filters']['#type']);
    $this->assertIsArray($widget_value[0]['filters']['type']['event_audience']);
    $this->assertIsArray($widget_value[0]['filters']['type']['event_audience']['#options']);
    $this->assertContains('Students', $widget_value[0]['filters']['type']['event_audience']['#options']);
    $this->assertContains('Everyone', $widget_value[0]['filters']['type']['event_audience']['#options']);
    $this->assertIsArray($widget_value[0]['filters']['type']['event_subject']['#options']);
    $this->assertContains('Arts/Media', $widget_value[0]['filters']['type']['event_subject']['#options']);
    $this->assertIsArray($widget_value[0]['filters']['type']['event_types']['#options']);
    $this->assertContains('Class/Seminar', $widget_value[0]['filters']['type']['event_types']['#options']);
    $this->assertIsArray($widget_value[0]['filters']['group_id']);
    $this->assertContains('Stanford Web Services', $widget_value[0]['filters']['group_id']['#options']);
    $this->assertFalse($widget_value[0]['filters']['group_id']['#multiple']);
    $this->assertIsArray($widget_value[0]['filters']['venue_id']);
    $this->assertFalse($widget_value[0]['filters']['venue_id']['#multiple']);
    $this->assertContains('Cardinal Hall', $widget_value[0]['filters']['venue_id']['#options']);

  }

}
