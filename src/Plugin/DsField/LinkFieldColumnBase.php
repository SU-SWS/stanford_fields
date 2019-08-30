<?php

namespace Drupal\stanford_fields\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Base field for getting the link url or label.
 */
abstract class LinkFieldColumnBase extends DsFieldBase {

  /**
   * Get the field value for the configured delta.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|null
   *   Field list values.
   */
  protected function getFieldValue() {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entity();
    $config = $this->getConfiguration();
    $field_name = $config['field']['field_name'];

    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $field = $entity->get($field_name);
    if ($field_value = $field->getValue()) {
      $delta = $config['field']['delta'] ?? 0;
      return $field_value[$delta] ?? NULL;
    }
  }

}
