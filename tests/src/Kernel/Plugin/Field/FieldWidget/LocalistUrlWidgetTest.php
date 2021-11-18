<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_fields\Plugin\Field\FieldWidget\LocalistUrlWidget;

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

  /**
   * Test the settings form and the summary.
   */
  public function testSettingsForm() {

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $config = [
      'field_definition' => $field_def,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $definition = [];
    $widget = LocalistUrlWidget::create(\Drupal::getContainer(), $config, '', $definition);

    $summary = $widget->settingsSummary();
    $this->assertCount(1, $summary);
    $this->assertEquals('No Base URL Provided', (string) $summary[0]);

    $config = [
      'field_definition' => $field_def,
      'settings' => [
        'base_url' => 'https://stanford.enterprise.localist.com',
      ],
      'third_party_settings' => [],
    ];

    $widget = LocalistUrlWidget::create(\Drupal::getContainer(), $config, '', $definition);
    $summary = $widget->settingsSummary();
    $this->assertCount(1, $summary);
    $this->assertEquals('Base URL: https://stanford.enterprise.localist.com', (string) $summary[0]);

    $form = [];
    $form_state = new FormState();
    $element = $widget->settingsForm($form, $form_state);
    $this->assertCount(4, $element);
    $this->assertEquals("https://stanford.enterprise.localist.com", $element['base_url']['#default_value']);
    $this->assertArrayHasKey('select_distinct', $element);
    $element['#parents'] = [];

    $validate_error = $widget->validateUrl($element, $form_state, $form);
    $this->assertCount(1, $form_state->getErrors());

    $values['0']['filters'] = [];
    $this->assertEmpty($widget->massageFormValues($values, $form, $form_state));

    $values = $this->getValidValue();
    $massaged_values = $widget->massageFormValues($values, $form, $form_state);
    $this->assertCount(1, $massaged_values);

    // Test for exceptions in the ajax calls.
    $bad_data = $widget->fetchLocalistData('https://www.nowhere.com');
    $this->assertCount(0, $bad_data);


  }

  /**
   * Returns valid form submission values.
   */
  protected function getValidValue() {
    return array (
      0 =>
      array (
        'uri' => 'https://stanford.enterprise.localist.com/api/2/events?group_id=37955145294460&days=365',
        'title' => '',
        'attributes' =>
        array (
        ),
        'filters' =>
        array (
          'type' =>
          array (
            'event_audience' =>
            array (
            ),
            'event_subject' =>
            array (
            ),
            'event_types' =>
            array (
              37952570025304 => "37952570025304",
            ),
          ),
          'group_id' => '37955145294460',
          'venue_id' => '',
          'match' => '',
        ),
        '_weight' => '0',
        '_original_delta' => 0,
      ),
    );
  }

}
