<?php

namespace Drupal\stanford_fields\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * A paragraphs behavior plugin to provide width and admin label storage.
 *
 * @ParagraphsBehavior(
 *   id = "theme_settings",
 *   label = "Theme Settings",
 *   description = "Some storage capabilities for react paragraphs widget.",
 *   deriver = "\Drupal\stanford_fields\Plugin\Derivative\ParagraphBehavior"
 * )
 */
class ThemeSettings extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    foreach ($this->pluginDefinition['configuration'] as $key => $config_settings) {
      $field = [];
      foreach ($config_settings as $field_key => $field_value) {
        $field["#$field_key"] = $field_value;
      }
      $form[$key] = $field;
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Nothing to modify on the paragraph itself.
  }

}
