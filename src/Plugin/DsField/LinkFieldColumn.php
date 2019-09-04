<?php

namespace Drupal\stanford_fields\Plugin\DsField;

use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the link as plain text.
 *
 * @DsField(
 *   id = "stanford_fields_link_field_column",
 *   deriver = "Drupal\stanford_fields\Plugin\Derivative\LinkFieldDeriver"
 * )
 */
class LinkFieldColumn extends DsFieldBase {

  /**
   * {@inheritDoc}}
   */
  public function build() {
    $text = $this->getLabel();

    if ($this->getPluginDefinition()['column'] == 'uri') {
      $text = NULL;
      if ($url = $this->getUrl()) {
        $text = $url->toString();
      }
    }

    return $text ? ["#plain_text" => $text] : [];
  }

  /**
   * Get the label column on the link field.
   *
   * @return string
   *   Link label.
   */
  protected function getLabel() {
    if ($value = $this->getFieldValue()) {
      return $value['title'];
    }
  }

  /**
   * Create a url object from user input.
   *
   * @return \Drupal\Core\Url
   *   Constructed object.
   */
  protected function getUrl() {
    $value = $this->getFieldValue();
    $uri = $value['uri'];
    if (empty($uri)) {
      return;
    }
    try {
      return Url::fromUri($uri);
    }
    catch (\Exception $e) {
      try {
        return Url::fromUserInput($uri);
      }
      catch (\Exception $e) {
        return Url::fromRoute($uri);
      }
    }
  }

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
