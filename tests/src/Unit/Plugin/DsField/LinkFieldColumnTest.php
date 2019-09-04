<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\DsField;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\stanford_fields\Plugin\DsField\LinkFieldColumn;
use Drupal\Tests\UnitTestCase;

/**
 * Class LinkFieldColumnLabelTextTest.
 *
 * @group stanford_fields
 * @coversDefaultClass \Drupal\stanford_fields\Plugin\DsField\LinkFieldColumn
 */
class LinkFieldColumnTest extends UnitTestCase {

  /**
   * Mock entity interface.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * If the get field values callback should return something.
   *
   * @var bool
   */
  protected $returnFieldValues = TRUE;

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

    $url = $this->createMock(Url::class);
    $url->method('getOptions')->willReturn([]);
    $url->method('toString')->willReturn('/foo-bar');

    $path_validator = $this->createMock(PathValidatorInterface::class);
    $path_validator->method('getUrlIfValidWithoutAccessCheck')
      ->willReturn($url);

    $url_assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);

    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $url_generator->method('generateFromRoute')->willReturn('/foo-bar');

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
   * Test the field outputs something.
   */
  public function testLabelDerivativeWithValues() {
    $configuration = [
      'entity' => $this->entity,
      'field' => ['field_name' => 'field_foo'],
    ];
    $plugin = LinkFieldColumn::create(new ContainerBuilder(), $configuration, '', ['column' => 'label']);
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => 'Foo Bar'], $element);
  }

  /**
   * Make sure the field is empty when no field value is present.
   */
  public function testLabelDerivativeWithoutValues() {
    $this->returnFieldValues = FALSE;

    $configuration = [
      'entity' => $this->entity,
      'field' => ['field_name' => 'field_foo'],
    ];
    $plugin = LinkFieldColumn::create(new ContainerBuilder(), $configuration, '', ['column' => 'label']);
    $element = $plugin->build();
    $this->assertEmpty($element);
  }

  /**
   * Test a variety of possible field values to make sure nothing breaks.
   */
  public function testUriDerivative() {
    $configuration = [
      'entity' => $this->entity,
      'field' => ['field_name' => 'field_foo'],
    ];
    $plugin = LinkFieldColumn::create(new ContainerBuilder(), $configuration, '', ['column' => 'uri']);

    $this->uriValue = '';
    $element = $plugin->build();
    $this->assertEmpty($element);

    $this->uriValue = '/foo-bar';
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => '/foo-bar'], $element);

    $this->uriValue = 'internal:/foo-bar';
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => '/foo-bar'], $element);

    $this->uriValue = 'user.login';
    $element = $plugin->build();
    $this->assertEquals(['#plain_text' => '/foo-bar'], $element);
  }

  /**
   * Field list get value callback.
   */
  public function getValueCallback() {
    if ($this->returnFieldValues) {
      return [['title' => 'Foo Bar', 'uri' => $this->uriValue]];
    }
    return [];
  }

}
