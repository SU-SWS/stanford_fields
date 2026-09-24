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
  id: 'preact_combo_box',
  title: new TranslatableMarkup('Preact ComboBox'),
)]
class PreactComboBox extends PreactFiltersBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    $form['#attached']['library'][] = 'stanford_fields/combobox';
  }

}
