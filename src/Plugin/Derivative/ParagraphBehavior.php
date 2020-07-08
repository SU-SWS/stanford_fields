<?php

namespace Drupal\stanford_fields\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\stanford_fields\ThemeSettingsBehaviorManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ParagraphBehavior extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\stanford_fields\ThemeSettingsBehaviorManagerInterface
   */
  protected $themeSettingsManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.paragraph_behaviors.theme_settings_manager')
    );
  }

  /**
   * ParagraphBehavior constructor.
   *
   * @param $base_plugin_id
   * @param \Drupal\stanford_fields\ThemeSettingsBehaviorManagerInterface $theme_settings_manager
   */
  public function __construct($base_plugin_id, ThemeSettingsBehaviorManagerInterface $theme_settings_manager) {
    $this->themeSettingsManager = $theme_settings_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->themeSettingsManager->getDefinitions() as $id => $definition) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['label'] = $definition['label'];
      $this->derivatives[$id]['description'] = $definition['description'] ?? $base_plugin_definition['description'];
      $this->derivatives[$id]['configuration'] = $definition['configuration'];
    }
    return $this->derivatives;
  }

}
