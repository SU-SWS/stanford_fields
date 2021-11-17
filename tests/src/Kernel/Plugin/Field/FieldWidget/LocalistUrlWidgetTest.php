<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_fields\Plugin\Field\FieldWidget\LocalistUrlWidget;

/**
 * Class LocalistUrlWidgetTest
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
    //$this->installEntitySchema('date_format');

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

    // Set the cache so we don't have to fetch xml as we have other tests
    // for that.
    $key_val = [
      'one' => 'One',
      'two' => 'Two',
      'three' => 'Three',
    ];


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
        'base_url' => 'https://stanford.enterprise.localist.com'
        ],
      ])
      ->removeComponent('created')
      ->save();

    $node->set('su_localist_url', [
      [
        'uri' => 'https://stanford.enterprise.localist.com/api/2/events?group_id=37955145294460&days=365',
        'title' => '',
        'options' => ''
      ],
    ]);

    $form = [];
    $form_state = new FormState();

    $entity_form_display->buildForm($node, $form, $form_state);

    $widget_value = $form['field_date']['widget'][0]['value'];

    $this->assertEquals('link', $widget_value['#type']);
    $this->assertEquals('uri', $widget_value['#uri']);
  }

  /**
   * Test the settings form and the summary.
   */
  public function testSettingsForm() {
    $ten_years = 60 * 60 * 24 * 365 * 10;

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $config = [
      'field_definition' => $field_def,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $definition = [];
    $widget = LocalistUrlWidget::create(\Drupal::getContainer(), $config, '', $definition);
    $summary = [];
    $this->assertEquals($summary, $widget->settingsSummary()[0]);

    $form = [];
    $form_state = new FormState();
    $element = $widget->settingsForm($form, $form_state);

    $this->assertEquals($element, $element['start']['#default_value']);
  }

}
