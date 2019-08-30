<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Derivative;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\stanford_fields\Plugin\Derivative\LinkFieldDeriver;
use Drupal\Tests\UnitTestCase;

/**
 * Class LinkFieldDeriverTest.
 *
 * @package Drupal\Tests\stanford_media\Unit\Plugin\Derivative
 */
class LinkFieldDeriverTest extends UnitTestCase {

  /**
   * Derivates are formed correctly.
   */
  public function testDerivative() {
    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')
      ->will($this->returnCallback([$this, 'entityLoadCallback']));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $field_manager->method('getFieldMapByFieldType')->willReturn([
      'node' => [
        'field_foo' => [
          'type' => 'link',
          'bundles' => ['foo', 'bar'],
        ],
      ],
    ]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_field.manager', $field_manager);

    $plugin = LinkFieldDeriver::create($container, 'stanford_fields_link_field_column_label');
    $derivatives = $plugin->getDerivativeDefinitions(['foo' => 'bar']);

    $expected = [
      'stanford_fields_node_foo_field_foo' => [
        'foo' => 'bar',
        'provider' => 'stanford_fields',
        'title' => 'Foo: Label',
        'entity_type' => 'node',
        'ui_limit' => [
          0 => 'foo|*',
        ],
        'field_name' => 'field_foo',
      ],
    ];

    $this->assertArrayEquals($expected, $derivatives);
  }

  /**
   * Entity storage load callback.
   */
  public function entityLoadCallback($field_id) {
    $field_config = $this->createMock(FieldConfigInterface::class);
    $field_config->method('label')->willReturn('Foo');

    if ($field_id == 'node.foo.field_foo') {
      return $field_config;
    }
  }

}
