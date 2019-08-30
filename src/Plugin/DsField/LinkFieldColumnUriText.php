<?php

namespace Drupal\stanford_fields\Plugin\DsField;

use Drupal\Core\Url;

/**
 * Plugin that renders the link as plain text.
 *
 * @DsField(
 *   id = "stanford_fields_link_field_column_uri",
 *   deriver = "Drupal\stanford_fields\Plugin\Derivative\LinkFieldDeriver"
 * )
 */
class LinkFieldColumnUriText extends LinkFieldColumnBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($value = $this->getFieldValue()) {
      $uri = $value['uri'];

      return [
        "#plain_text" => $this->getUrl($uri)->toString(),
      ];
    }
  }

  /**
   * Create a url object from user input.
   *
   * @param string $uri
   *   User entered text.
   *
   * @return \Drupal\Core\Url
   *   Constructed object.
   */
  protected function getUrl($uri) {
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

}
