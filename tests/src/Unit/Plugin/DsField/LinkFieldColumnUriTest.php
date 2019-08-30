<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\DsField;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\stanford_fields\Plugin\DsField\LinkFieldColumnUriText;
use Drupal\Tests\UnitTestCase;

/**
 * Class LinkFieldColumnUriTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\DsField\LinkFieldColumnUriText
 */
class LinkFieldColumnUriTest extends UnitTestCase {

  /**
   * Mock entity.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The field uri value.
   *
   * @var string
   */
  protected $uriValue = '';

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $path_validator = $this->createMock(PathValidatorInterface::class);

    $url_assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $url_generator = $this->createMock(UrlGeneratorInterface::class);

    $container = new ContainerBuilder();
    $container->set('path.validator', $path_validator);
    $container->set('unrouted_url_assembler', $url_assembler);
    $container->set('url_generator', $url_generator);
    \Drupal::setContainer($container);

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn(1);
    $field_list->method('getValue')
      ->will($this->returnCallback([$this, 'getValueCallback']));

    $this->entity = $this->createMock(EntityTypeInterface::class);
    $this->entity->method('get')->willReturn($field_list);
  }

  /**
   * Test a variety of possible field values to make sure nothing breaks.
   */
  public function testPluginWithValues() {
    $configuration = [
      'entity' => $this->entity,
      'field' => ['field_name' => 'field_foo'],
    ];
    $plugin = LinkFieldColumnUriText::create(new ContainerBuilder(), $configuration, '', []);

    $this->uriValue = '';
    $element = $plugin->build();
    $this->assertNull($element);

    $this->uriValue = '/foo-bar';
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => NULL], $element);

    $this->uriValue = 'internal:/foo-bar';
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => NULL], $element);

    $this->uriValue = 'user.login';
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => NULL], $element);
  }

  /**
   * Get Field value callback.
   */
  public function getValueCallback() {
    if (empty($this->uriValue)) {
      return [];
    }
    return [['uri' => $this->uriValue]];
  }

}
