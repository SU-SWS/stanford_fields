<?php

namespace Drupal\Tests\stanford_fields\Kernel\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;

/**
 * Class FieldCacheTest
 *
 * @package Drupal\Tests\stanford_fields\Kernel\Service
 */
class FieldCacheTest extends StanfordFieldKernelTestBase {

  /**
   * Array of cache tags scheduled for invalidation.
   *
   * @var array
   */
  protected $invalidatedTags = [];

  /**
   * Date field format to save the date as.
   *
   * @var string
   */
  protected $dateFieldFormat = DateTimeItemInterface::DATE_STORAGE_FORMAT;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();

    // Create a comment field attached to a host 'entity_test' entity.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'datetime',
      'field_name' => 'field_date',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_date',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'daterange',
      'field_name' => 'field_daterange',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_daterange',
    ])->save();

    $cache_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_invalidator->method('invalidateTags')
      ->will($this->returnCallback([$this, 'invalidateTagsCallback']));
    \Drupal::getContainer()->set('cache_tags.invalidator', $cache_invalidator);
  }

  /**
   * Test that the field value will trigger an invalidation.
   */
  public function testDateInvalidation() {
    \Drupal::service('stanford_fields.field_cache')
      ->invalidateDateFieldsCache();
    $this->assertEmpty($this->invalidatedTags);

    $node = Node::create([
      'type' => 'page',
      'title' => 'foo',
      'field_date' => date($this->dateFieldFormat, time() + 60 * 60 * 24 * 3),
    ]);
    $node->save();

    $this->invalidatedTags = [];
    \Drupal::service('stanford_fields.field_cache')
      ->invalidateDateFieldsCache();
    $this->assertEmpty($this->invalidatedTags);

    \Drupal::state()
      ->set('stanford_fields.dates_cleared', time() - 60 * 60 * 24 * 4);
    $node->set('field_date', date($this->dateFieldFormat, time() - 60 * 60 * 24 * 3))
      ->save();
    $this->invalidatedTags = [];
    \Drupal::service('stanford_fields.field_cache')
      ->invalidateDateFieldsCache();
    $this->assertTrue(in_array('node:' . $node->id(), $this->invalidatedTags));

    \Drupal::state()
      ->set('stanford_fields.dates_cleared', time() - 60 * 60 * 24 * 4);
    $daterange = [
      'value' => date($this->dateFieldFormat, time() - 60 * 60 * 24 * 10),
      'end_value' => date($this->dateFieldFormat, time() - 60 * 60 * 24),
    ];
    $node->set('field_date', [])
      ->set('field_daterange', $daterange)
      ->save();

    $this->invalidatedTags = [];
    \Drupal::service('stanford_fields.field_cache')
      ->invalidateDateFieldsCache();
    $this->assertTrue(in_array('node:' . $node->id(), $this->invalidatedTags));
  }

  /**
   * Run the same tests as above, but with a date time storage.
   */
  public function testDateTimeInvalidation() {
    $this->dateFieldFormat = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    FieldStorageConfig::load('node.field_date')
      ->setSetting('datetime_type', 'datetime')
      ->save();
    $this->testDateInvalidation();
  }

  /**
   * Cache invalidation callback.
   */
  public function invalidateTagsCallback($tags) {
    $this->invalidatedTags = $tags;
  }

}
