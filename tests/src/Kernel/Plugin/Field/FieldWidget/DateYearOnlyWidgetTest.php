<?php

namespace Drupal\Tests\stanford_fields\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\stanford_fields\Plugin\Field\FieldWidget\DateYearOnlyWidget;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;

/**
 * Class DateYearOnlyWidgetTest
 *
 * @group
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldWidget\DateYearOnlyWidget
 */
class DateYearOnlyWidgetTest extends StanfordFieldKernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
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

    $this->assertEquals('datelist', $widget_value['#type']);
    $this->assertEquals($range, $widget_value['#date_year_range']);
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
    $widget = DateYearOnlyWidget::create(\Drupal::getContainer(), $config, '', $definition);
    $summary = sprintf('Years %s to %s', date('Y', time() - $ten_years), date('Y', time() + $ten_years));
    $this->assertEquals($summary, $widget->settingsSummary()[0]);

    $form = [];
    $form_state = new FormState();
    $element = $widget->settingsForm($form, $form_state);

    $this->assertEquals(date('Y', time() - $ten_years), $element['start']['#default_value']);
    $this->assertEquals(date('Y', time() + $ten_years), $element['end']['#default_value']);
  }

}
