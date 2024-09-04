<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cshs\Component\CshsOption;
use Drupal\cshs\Element\CshsElement;

/**
 * Defines the 'taxonomy_label_hierarchy' field widget.
 */
#[FieldWidget(
  id: 'taxonomy_label_hierarchy',
  label: new TranslatableMarkup('Taxonomy Label Hierarchy'),
  field_types: ['entity_reference'],
  multiple_values: TRUE,
)]
final class TaxonomyLabelHierarchyWidget extends OptionsWidgetBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Make sure the CSHS module is available.
    return \Drupal::moduleHandler()->moduleExists('cshs');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $options = $this->getOptions($items->getEntity());
    $selected_items = $this->getSelectedOptions($items);

    $element += [
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    $grouped_options = [];
    foreach ($options as $id => $option) {
      if (!str_starts_with((string) $option, '-')) {
        $parent = (string) $option;
        continue;
      }
      $grouped_options[$parent][$id] = substr((string) $option, 1);
    }

    foreach ($grouped_options as $group_label => $group_options) {
      $key = preg_replace('@[^a-z0-9_.]+@', '_', mb_strtolower($group_label));
      foreach ($group_options as $tid => &$option) {
        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($tid);
        $option = new CshsOption(ltrim($option, "-"), $term->get('parent')
          ->getString(), $group_label);
      }

      $num_values = $form_state->get($key);
      $default_values = array_values(array_intersect($selected_items, array_keys($group_options)));

      if (!$num_values) {
        $num_values = max(1, count($default_values));
        $form_state->set($key, $num_values);
      }

      $element[$key] = [
        '#type' => 'fieldset',
        '#title' => $group_label,
        '#prefix' => '<div id="' . $key . '">',
        '#suffix' => '</div>',
      ];

      for ($i = 0; $i < $num_values; $i++) {
        $element[$key][$i]['target_id'] = [
          '#type' => CshsElement::ID,
          '#labels' => [],
          '#options' => $group_options,
          '#multiple' => $this->multiple,
          '#default_value' => $default_values[$i] ?? NULL,
        ];
      }
      $element[$key]['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add More'),
        '#name' => $key,
        '#submit' => [[self::class, 'addOne']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [self::class, 'addMoreCallback'],
          'wrapper' => $key,
        ],
      ];
    }

    return $element;
  }

  /**
   * Ajax callback to add another.
   *
   * @codeCoverageIgnore
   */
  public static function addMoreCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $path = $trigger['#array_parents'];
    array_pop($path);
    return NestedArray::getValue($form, $path);
  }

  /**
   * Form callback to add another.
   *
   * @codeCoverageIgnore
   */
  public static function addOne(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $name = $trigger['#name'];
    $form_state->set($name, $form_state->get($name) + 1);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $value = [];
    foreach (Element::children($element) as $key) {
      foreach (Element::children($element[$key]) as $delta) {
        if (isset($element[$key][$delta]['target_id']['#value']) && is_array($element[$key][$delta]['target_id']['#value'])) {
          $value[] = end($element[$key][$delta]['target_id']['#value']);
        }
      }
    }
    $element['#value'] = array_filter(array_unique($value));
    parent::validateElement($element, $form_state);
  }

}
