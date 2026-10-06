<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for preact exposed filters.
 *
 * @codeCoverageIgnore
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
    $view_dom_id = (string) $form_state->get('view')->dom_id;

    // Include part of the view's dom id so the wrapper is unique when the same
    // filter is used by multiple views on a page, but stays stable across
    // views AJAX requests.
    $id = Html::cleanCssIdentifier("preact-$field_id-" . substr($view_dom_id, 0, 8));

    $form[$field_id]['#prefix'] = "<div id='$id' class='preact-filter $pluginClass'>";
    $form[$field_id]['#suffix'] = '</div>';

    $options = BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
    $js_options = [];
    foreach ($options as $key => $value) {
      $js_options[] = ['value' => (string) $key, 'label' => $value];
    }
    // Key by the wrapper id. Drupal deep merges AJAX settings, which would
    // merge lists by index and mix up filters from different views.
    $form['#attached']['drupalSettings']['preactFilters'][$this->getPluginId()][$id] = [
      'id' => $id,
      'options' => $js_options,
      'viewId' => $view_dom_id,
    ];

  }

}
