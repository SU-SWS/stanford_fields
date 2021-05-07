<?php

namespace Drupal\Tests\stanford_fields\Kernel\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Class FieldCacheTest
 *
 * @package Drupal\Tests\stanford_fields\Kernel\Service
 */
class FieldCacheTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'stanford_fields',
    'node',
    'user',
    'datetime',
    'field',
  ];

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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    $cache_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_invalidator->method('invalidateTags')
      ->will($this->returnCallback([$this, 'invalidateTagsCallback']));

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
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

    $node->set('field_date', date($this->dateFieldFormat, time() - 60 * 60 * 24 * 3))
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