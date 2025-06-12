<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "taxonomy_label_hierarchy_checkbox",
 *   label = @Translation("Hierarchy taxonomy labels Radios"),
 * )
 */
class PreactTaxonomyLabelHierarchyCheckbox extends PreactFiltersBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    $form['#attached']['library'][] = 'stanford_fields/hierarchy-checkbox';
  }

}
