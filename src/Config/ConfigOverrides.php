<?php

namespace Drupal\stanford_fields\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\stanford_fields\ThemeSettingsBehaviorManagerInterface;

class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * @var \Drupal\stanford_fields\ThemeSettingsBehaviorManagerInterface
   */
  protected $behaviorManager;

  public function __construct(ThemeSettingsBehaviorManagerInterface $behavior_manager) {
    $this->behaviorManager = $behavior_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach ($this->behaviorManager->getDefinitions() as $id => $behavior_plugin) {
      if (isset($behavior_plugin['bundles'])) {
        foreach ($behavior_plugin['bundles'] as $paragraph_bundle) {
          if (in_array("paragraphs.paragraphs_type.$paragraph_bundle", $names)) {
            $plugin_name = "theme_settings:$id" ;
            $overrides["paragraphs.paragraphs_type.$paragraph_bundle"]['behavior_plugins'][$plugin_name]['enabled'] = TRUE;
          }
        }
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'StanfordFieldsConfigOverrides';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
