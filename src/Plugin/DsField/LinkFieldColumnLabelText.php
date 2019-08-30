<?php

namespace Drupal\stanford_fields\Plugin\DsField;

/**
 * Plugin that renders the link as plain text.
 *
 * @DsField(
 *   id = "stanford_fields_link_field_column_label",
 *   deriver = "Drupal\stanford_fields\Plugin\Derivative\LinkFieldDeriver"
 * )
 */
class LinkFieldColumnLabelText extends LinkFieldColumnBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($value = $this->getFieldValue()) {
      $label = $value['title'];
      return [
        "#plain_text" => $label,
      ];
    }
  }

}
