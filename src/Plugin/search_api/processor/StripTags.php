<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\search_api\processor\HtmlFilter;

/**
 * Strips HTML tags from fulltext fields and decodes HTML entities.
 *
 * @SearchApiProcessor(
 *   id = "strip_tags",
 *   label = @Translation("HTML filter (3rd party APIs)"),
 *   description = @Translation("Strips HTML tags from fulltext fields and decodes HTML entities. Use this processor when indexing HTML data for external API's such as Algolia – for example, node bodies for certain text formats. The processor also allows to boost (or ignore) the contents of specific elements. This differs from the "HTML Filter" in that it does not."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = -15,
 *     "preprocess_query" = -15,
 *   }
 * )
 */
class StripTags extends HtmlFilter {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['title']['#access'] = FALSE;
    $form['alt']['#access'] = FALSE;
    $form['tags']['#description'] = $this->t('Specify tags to retain, in <a href=":url">YAML file format</a>. Use a value other than 1. ie: <code>div: 2</code>', [':url' => 'https://en.wikipedia.org/wiki/YAML']);

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  protected function processFieldValue(&$value, $type) {
    if (!is_string($value)) {
      return;
    }

    // Remove invisible content.
    $text = $this->removeInvisibleHtmlElements($value);
    $is_text_type = $this->getDataTypeHelper()->isTextType($type);
    if ($is_text_type) {
      // Let removed tags still delimit words.
      $text = str_replace(['<', '>'], [' <', '> '], $text);
      $text = $this->handleAttributes($text);
    }
    if ($this->configuration['tags'] && $is_text_type) {
      $text = strip_tags($text, '<' . implode('><', array_keys($this->configuration['tags'])) . '>');
      $value = $this->normalizeText(trim($text));
    }
    else {
      $text = strip_tags($text);
      $value = $this->normalizeText(trim($text));
    }
  }

}
