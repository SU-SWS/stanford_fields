<?php

namespace Drupal\stanford_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDatelistWidget;

/**
 * Plugin to provide a date widget that only collects the year.
 *
 * @FieldWidget(
 *   id = "datetime_year_only",
 *   label = @Translation("Year Only"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateYearOnlyWidget extends DateTimeDatelistWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $ten_years = 60 * 60 * 24 * 365 * 10;
    $settings = [
      'start' => date('Y', time() - $ten_years),
      'end' => date('Y', time() + $ten_years),
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Years @start to @end', [
      '@start' => $this->getSetting('start'),
      '@end' => $this->getSetting('end'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['date_order']['#type'] = 'hidden';

    $year_range = range(1900, 2100);
    $element['start'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Year'),
      '#options' => array_combine($year_range, $year_range),
      '#default_value' => $this->getSetting('start'),
    ];
    $element['end'] = [
      '#type' => 'select',
      '#title' => $this->t('End Year'),
      '#options' => array_combine($year_range, $year_range),
      '#default_value' => $this->getSetting('end'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#date_part_order'] = ['year'];
    $element['value']['#date_year_range'] = $this->getSetting('start') . ':' . $this->getSetting('end');
    return $element;
  }

}
