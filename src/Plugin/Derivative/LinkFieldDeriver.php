<?php

namespace Drupal\stanford_fields\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a derivative for bundle fields.
 */
class LinkFieldDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The base plugin ID that the derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DsEntityRow object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct($base_plugin_id, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $field_config_storage = $this->entityTypeManager->getStorage('field_config');

    // Get all link fields and loop through them as entity/bundle/field.
    $fields = $this->entityFieldManager->getFieldMapByFieldType('link');
    foreach ($fields as $entity_type => $field) {
      foreach ($field as $field_name => $usage) {
        foreach ($usage['bundles'] as $bundle_name) {

          // Not everything has instance info.
          // Keep going if nothing is available.
          $field_info = $field_config_storage->load("$entity_type.$bundle_name.$field_name");
          if (is_null($field_info)) {
            continue;
          }

          // OK, define it.
          $id = "stanford_fields_" . $entity_type . "_" . $bundle_name . "_" . $field_name;
          $this->derivatives[$id] = $base_plugin_definition;
          $this->derivatives[$id] += [
            'provider' => 'stanford_fields',
            'title' => $field_info->label() . $this->getSuffix(),
            'entity_type' => $entity_type,
            'ui_limit' => [$bundle_name . "|*"],
            'field_name' => $field_name,
          ];
        }
      }
    }

    return $this->derivatives;
  }

  /**
   * Returns a string suffix for the derivative label based on the calling
   * plugin.
   *
   * @return string
   *   The suffix string to append to the field name.
   */
  private function getSuffix() {
    $keys = [
      'stanford_fields_link_field_column_label' => ': Label',
      'stanford_fields_link_field_column_uri' => ': URI',
    ];

    if (array_key_exists($this->basePluginId, $keys)) {
      return $keys[$this->basePluginId];
    }
  }

}
