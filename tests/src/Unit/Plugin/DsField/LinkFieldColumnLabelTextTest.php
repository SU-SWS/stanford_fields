<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\DsField;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\stanford_fields\Plugin\DsField\LinkFieldColumnLabelText;
use Drupal\Tests\UnitTestCase;

/**
 * Class LinkFieldColumnLabelTextTest.
 *
 * @group stanford_fields
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\DsField\LinkFieldColumnLabelText
 */
class LinkFieldColumnLabelTextTest extends UnitTestCase {

  /**
   * Mock entity interface.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * If the get field values callback should return something.
   *
   * @var bool
   */
  protected $returnFieldValues = TRUE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn(1);
    $field_list->method('getValue')
      ->will($this->returnCallback([$this, 'getValueCallback']));

    $this->entity = $this->createMock(EntityTypeInterface::class);
    $this->entity->method('get')->willReturn($field_list);
  }

  /**
   * Test the field outputs something.
   */
  public function testPluginWithValues() {
    $configuration = [
      'entity' => $this->entity,
      'field' => ['field_name' => 'field_foo'],
    ];
    $plugin = LinkFieldColumnLabelText::create(new ContainerBuilder(), $configuration, '', []);
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => 'Foo Bar'], $element);
  }

  /**
   * Make sure the field is empty when no field value is present.
   */
  public function testPluginWithOutValues() {
    $this->returnFieldValues = FALSE;

    $configuration = [
      'entity' => $this->entity,
      'field' => ['field_name' => 'field_foo'],
    ];
    $plugin = LinkFieldColumnLabelText::create(new ContainerBuilder(), $configuration, '', []);
    $element = $plugin->build();
    $this->assertNull($element);
  }

  /**
   * Field list get value callback.
   */
  public function getValueCallback() {
    if ($this->returnFieldValues) {
      return [['title' => 'Foo Bar']];
    }
    return [];
  }

}
