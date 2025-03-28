<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "combo_box",
 *   label = @Translation("Javascript ComboBox"),
 * )
 */
class ComboBox extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    $form['#attached']['library'][] = 'stanford_fields/combobox';
    $field_id = $this->getExposedFilterFieldId();
    $form[$field_id]['#prefix'] = '<div class="select-preact">';
    $form[$field_id]['#suffix'] = '</div>';
  }

}
