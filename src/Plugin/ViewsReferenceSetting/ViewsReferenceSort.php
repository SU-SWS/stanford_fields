<?php

namespace Drupal\stanford_fields\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The views reference setting pager plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "sort",
 *   label = @Translation("Sorting"),
 *   default_value = null,
 * )
 */
class ViewsReferenceSort extends PluginBase implements ViewsReferenceSettingInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * @param array $configuration
   *   Plugin configuration.
   * @param $plugin_id
   *   Plugin ID.
   * @param $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $view = $this->configuration['view_name'];
    $display_id = $this->configuration['display_id'];
    if (!$view || !$display_id) {
      return;
    }
    /** @var \Drupal\views\Entity\View $view */
    $view = $this->entityTypeManager->getStorage('view')->load($view);

    $display = $view->getDisplay($display_id);
    $default = $view->getDisplay('default');
    $sorts = $display['display_options']['sorts'] ?? $default['display_options']['sorts'];
    $exposed_sorts = array_filter($sorts, fn($sort) => $sort['exposed']);
    $sort_options = [];
    foreach ($exposed_sorts as $sort) {
      $sort_options[$sort['id']] = $sort['expose']['label'];
    }

    if (empty($sort_options)) {
      $form_field['#access'] = FALSE;
    }

    $form_field['#type'] = 'container';
    $form_field['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $sort_options,
      '#empty_option' => $this->t('Default Sort'),
      '#default_value' => $form_field['#default_value']['field'] ?? NULL,
    ];

    $form_field['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort Direction'),
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
      ],
      '#default_value' => $form_field['#default_value']['direction'] ?? 'ASC',
    ];
    unset($form_field['#default_value']);
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (!empty($value)) {
      $view->setExposedInput([
        'sort_by' => $value['field'],
        'sort_order' => $value['direction'],
      ]);
    }
  }

}
