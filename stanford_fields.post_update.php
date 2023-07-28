<?php

/**
 * @file
 * stanford_fields.post_update.php
 */

/**
 * Uninstall response code condition module if it exists.
 */
function stanford_fields_post_update_disable_response_code_condition() {
  if (\Drupal::moduleHandler()->moduleExists('response_code_condition')) {
    \Drupal::service('module_installer')
      ->uninstall(['response_code_condition']);
  }
}
