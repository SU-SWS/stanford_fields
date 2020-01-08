<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Url;
use Drupal\stanford_fields\Plugin\Field\FieldFormatter\EntityTitleHeading;

/**
 * Class EntityTitleHeadingTest
 *
 * @group stanford_fields
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\Field\FieldFormatter\EntityTitleHeading
 */
class MultiMediaTest extends FieldFormatterTestBase {

  /**
   * Field formatter plugin.
   *
   * @var \Drupal\stanford_fields\Plugin\Field\FieldFormatter\EntityTitleHeading
   */
  protected $plugin;

  /**
   * Mock field item list.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $fieldItemList;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $field_definition = $this->createMock(FieldDefinitionInterface::class);

    $parent_entity = $this->createMock(FieldableEntityInterface::class);
    $parent_entity->method('toUrl')->willReturn(Url::fromUserInput('/foo-bar'));

    $parent = $this->createMock(TraversableTypedDataInterface::class);
    $parent->method('getValue')->willReturn($parent_entity);

    $this->fieldItemList = $this->createMock(FieldItemListInterface::class);
    $this->fieldItemList->method('getParent')->willReturn($parent);
    $this->fieldItemList->method('getValue')
      ->willReturn([['value' => 'Foo Bar']]);

    $configuration = [
      'field_definition' => $field_definition,
      'settings' => [],
      'label' => 'Foo',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ];
    $this->plugin = EntityTitleHeading::create($this->container, $configuration, '', []);
  }

  public function testPluginSettings() {

    // $this->assertArrayEquals([
    //   'tag' => 'h2',
    //   'linked' => FALSE,
    // ], EntityTitleHeading::defaultSettings());
    //
    // $form = [];
    // $form_state = new FormState();
    // $element = $this->plugin->settingsForm($form, $form_state);
    // $this->assertArrayEquals([
    //   'h1' => 'H1',
    //   'h2' => 'H2',
    //   'h3' => 'H3',
    //   'h4' => 'H4',
    //   'h5' => 'H5',
    // ], $element['tag']['#options']);
    //
    // $summary = $this->plugin->settingsSummary();
    // $this->assertNotEmpty($summary);
    // $this->assertEquals('Header level: h2', reset($summary)->render());
  }

  public function testViewElements() {

    // $this->plugin->setSetting('tag', 'h2');
    // $output = $this->plugin->viewElements($this->fieldItemList, 'en');
    // $this->assertEquals('h2', $output[0]['#tag']);
    // $this->assertEquals('html_tag', $output[0]['#type']);
    // $this->assertEquals('Foo Bar', $output[0]['#value']);
    //
    // $this->plugin->setSetting('tag', 'h1');
    // $output = $this->plugin->viewElements($this->fieldItemList, 'en');
    // $this->assertEquals('h1', $output[0]['#tag']);
    // $this->assertEquals('html_tag', $output[0]['#type']);
    // $this->assertEquals('Foo Bar', $output[0]['#value']);
    //
    // $this->isFrontPage = TRUE;
    // $output = $this->plugin->viewElements($this->fieldItemList, 'en');
    // $this->assertEmpty($output);
  }

  public function testLinkedViewElements() {
    // $this->plugin->setSetting('tag', 'h2');
    // $this->plugin->setSetting('linked', TRUE);
    // $output = $this->plugin->viewElements($this->fieldItemList, 'en');
    // $this->assertEquals('<a href="/foo-bar">Foo Bar</a>', $output[0]['#value']);
  }

}
