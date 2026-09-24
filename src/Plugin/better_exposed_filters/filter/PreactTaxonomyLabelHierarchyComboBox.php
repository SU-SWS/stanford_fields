<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Attribute\FiltersWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Default widget implementation.
 *
 * @codeCoverageIgnore
 */
#[FiltersWidget(
  id: 'taxonomy_label_hierarchy',
  title: new TranslatableMarkup('Hierarchy taxonomy labels combo box'),
)]
class PreactTaxonomyLabelHierarchyComboBox extends PreactFiltersBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    $form['#attached']['library'][] = 'stanford_fields/hierarchy-combo';
  }

}
