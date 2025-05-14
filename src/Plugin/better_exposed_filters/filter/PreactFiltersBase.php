<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for preact exposed filters.
 */
class PreactFiltersBase extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    $form['#attached']['library'][] = 'stanford_fields/bef-styles';
    $field_id = $this->getExposedFilterFieldId();
    $pluginClass = Html::cleanCssIdentifier($this->getPluginId());
    $id = Html::cleanCssIdentifier("preact-$field_id");

    $form[$field_id]['#prefix'] = "<div id='$id' class='preact-filter $pluginClass'>";
    $form[$field_id]['#suffix'] = '</div>';

    $options = BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
    $js_options = [];
    foreach ($options as $key => $value) {
      $js_options[] = ['value' => (string) $key, 'label' => $value];
    }
    $form['#attached']['drupalSettings']['preactFilters'][$this->getPluginId()][] = [
      'id' => $id,
      'options' => $js_options,
    ];

  }

}
