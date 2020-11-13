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
use Drupal\stanford_fields\Plugin\Field\FieldWidget\DateYearOnlyWidget;

/**
 * Class DateYearOnlyWidgetTest
 *
 * @group
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldWidget\DateYearOnlyWidget
 */
class DateYearOnlyWidgetTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'path_alias',
    'node',
    'user',
    'datetime',
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
    $this->installEntitySchema('date_format');

    NodeType::create(['type' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'type' => 'datetime',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_date',
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
    $entity_form_display->setComponent('field_date', ['type' => 'datetime_year_only'])
      ->removeComponent('created')
      ->save();

    $form = [];
    $form_state = new FormState();

    $entity_form_display->buildForm($node, $form, $form_state);

    $widget_value = $form['field_date']['widget'][0]['value'];
    $ten_years = 60 * 60 * 24 * 365 * 10;
    $range = date('Y', time() - $ten_years) . ':' . date('Y', time() + $ten_years);

    $this->assertEqual('datelist', $widget_value['#type']);
    $this->assertEqual($range, $widget_value['#date_year_range']);
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
    $widget = DateYearOnlyWidget::create(\Drupal::getContainer(), $config, '', $definition);
    $summary = 'Years 2010 to 2030';
    $this->assertEqual($summary, $widget->settingsSummary()[0]);

    $form = [];
    $form_state = new FormState();
    $element = $widget->settingsForm($form, $form_state);

    $ten_years = 60 * 60 * 24 * 365 * 10;

    $this->assertEqual(date('Y', time() - $ten_years), $element['start']['#default_value']);
    $this->assertEqual(date('Y', time() + $ten_years), $element['end']['#default_value']);
  }

}
