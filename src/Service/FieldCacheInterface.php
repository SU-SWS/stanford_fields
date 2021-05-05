<?php

namespace Drupal\stanford_fields\Service;

/**
 * Interface FieldCacheInterface.
 *
 * @package Drupal\stanford_fields\Service
 */
interface FieldCacheInterface {

  /**
   * Invalidate entities that have date field values that recently passed by.
   */
  public function invalidateDateFieldsCache();

}
