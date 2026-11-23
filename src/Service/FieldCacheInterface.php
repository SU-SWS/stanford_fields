<?php

namespace Drupal\stanford_fields\Service;

/**
 * Invalidates cache tags of entities whose date field values have passed.
 *
 * @package Drupal\stanford_fields\Service
 */
interface FieldCacheInterface {

  /**
   * Invalidate entities that have date field values that recently passed by.
   */
  public function invalidateDateFieldsCache();

}
