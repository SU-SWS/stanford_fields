<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldConfigInterface;

/**
 * Hooks for stanford fields functionality.
 */
class StanfordFieldsHooks {

  use StringTranslationTrait;

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(private readonly ConfigFactoryInterface $configFactory, private readonly EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Add checkbox to link field to enable relative validation.
   */
  #[Hook('form_field_config_edit_form_alter')]
  public function fieldConfigEditFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $form_state->get('field_config');
    if ($field_config->getType() == 'link') {
      $form['settings']['force_relative'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Force relative internal links'),
        '#default_value' => $field_config->getThirdPartySetting('stanford_fields', 'force_relative'),
        '#states' => [
          'invisible' => [
            ':input[name="settings[link_type]"]' => ['value' => '16'],
          ],
        ],
      ];
      $form['#entity_builders'][] = [$this, 'fieldConfigEntityBuilder'];
    }
  }

  /**
   * Adds an option to hide some parts of the fontawesome widget.
   */
  #[Hook('field_widget_third_party_settings_form')]
  public function fieldWidgetThirdPartySettings(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, array $form, FormStateInterface $form_state) {
    $element = [];
    if ($plugin->getPluginId() == 'fontawesome_icon_widget') {
      $element['hidden_settings'] = [
        '#type' => 'select',
        '#title' => $this->t('Hide additional settings'),
        '#default_value' => $plugin->getThirdPartySetting('stanford_fields', 'hidden_settings', []),
        '#multiple' => TRUE,
        '#options' => [
          'iconset' => $this->t('Icon Set'),
          'size' => $this->t('Size'),
          'fixed-width' => $this->t('Fixed Width'),
          'border' => $this->t('Border'),
          'invert' => $this->t('Invert Color'),
          'animation' => $this->t('Animation'),
          'pull' => $this->t('Pull'),
          'additional_classes' => $this->t('Additional Classes'),
          'duotone' => $this->t('Duotone Settings'),
          'masking' => $this->t('Icon Mask'),
          'power_transforms' => $this->t('Power Transforms'),
        ],
      ];
    }
    return $element;
  }

  /**
   * Hides the font awesome additional settings options.
   */
  #[Hook('field_widget_complete_fontawesome_icon_widget_form_alter')]
  public function fontawesomeIconWidgetFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context) {
    $hidden_settings = $context['widget']->getThirdPartySetting('stanford_fields', 'hidden_settings', []);
    foreach (Element::children($field_widget_complete_form['widget']) as $delta) {
      foreach ($hidden_settings as $hidden_setting) {
        $field_widget_complete_form['widget'][$delta]['settings'][$hidden_setting]['#access'] = FALSE;
      }
    }
  }

  /**
   * Field config form submission to save the third party settings.
   *
   * @param string $entity_type
   *   Entity type ID.
   * @param \Drupal\field\FieldConfigInterface $entity
   *   Submitted entity object.
   * @param array $form
   *   Submitted form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public function fieldConfigEntityBuilder(string $entity_type, FieldConfigInterface $entity, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['settings', 'force_relative'])) {
      $entity->setThirdPartySetting('stanford_fields', 'force_relative', TRUE);
      return;
    }
    $entity->unsetThirdPartySetting('stanford_fields', 'force_relative');
  }

  /**
   * Adds link field constraint.
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($fields as $field) {
      if ($field->getType() == 'link' && $field->getThirdPartySetting('stanford_fields', 'force_relative')) {
        $field->addConstraint('relative_internal_link', []);
      }
    }
  }

  /**
   * Add validation to field add form.
   */
  #[Hook('form_field_ui_field_storage_add_form_alter')]
  public function fieldUiStorageFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $entity_type = $form_state->get('entity_type_id');
    $isRevisionable = $this->entityTypeManager->getDefinition($form_state->get('entity_type_id'))
      ->isRevisionable();

    $field_prefix = $this->configFactory->get('field_ui.settings')
      ->get('field_prefix');

    $revision_table = $isRevisionable ? '_revision' : '';

    $form['field_name']['#maxlength'] = 48 - strlen("$entity_type{$revision_table}__$field_prefix$field_prefix");
    $form['field_name']['#description'] = $this->t('A unique machine-readable name containing letters, numbers, and underscores. The length of the name has been limited to %length characters to prevent database table hashing.', ['%length' => $form['field_name']['#maxlength']]);
  }

  /**
   * Invalidate date field caches.
   *
   * @codeCoverageIgnore
   */
  #[Hook('cron')]
  public function cron() {
    \Drupal::service('stanford_fields.field_cache')
      ->invalidateDateFieldsCache();
  }

  /**
   * Replace field type plugins for graphql compose.
   */
  #[Hook('graphql_compose_field_type_alter')]
  function graphqlComposeFieldTypeAlter(array &$field_types) {
    $field_types['image']['class'] = 'Drupal\stanford_fields\Plugin\GraphQLCompose\FieldType\ImageItem';
  }

  /**
   * Replace schema type plugins for graphql compose.
   */
  #[Hook('graphql_compose_graphql_type_alter')]
  function graphqlComposeGraphqlTypeAlter(array &$entity_types) {
    $entity_types['Image']['class'] = 'Drupal\stanford_fields\Plugin\GraphQLCompose\SchemaType\ImageType';
  }

}
