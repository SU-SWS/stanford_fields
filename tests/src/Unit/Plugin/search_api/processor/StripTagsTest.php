<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\search_api\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\stanford_fields\Plugin\search_api\processor\StripTags;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Test link field validation.
 */
class StripTagsTest extends UnitTestCase {

  #[TestWith([
    'original' => '<div>Foobar<div>',
    'expected' => 'Foobar',
    'isTextType' => FALSE,
  ])]
  #[TestWith([
    'original' => '<div>Foobar</div>',
    'expected' => '<div> Foobar </div>',
    'tags' => ['div'],
  ])]
  #[TestWith(['original' => 123, 'expected' => '123'])]
  public function testStripTags(string|int $original, string $expected, array $tags = [], bool $isTextType = TRUE) {
    $dataTypeHelper = $this->createMock(DataTypeHelperInterface::class);
    $dataTypeHelper->method('isTextType')->willReturn($isTextType);
    $elementInfo = $this->createMock(ElementInfoManagerInterface::class);
    $fieldsHelper = $this->createMock(FieldsHelperInterface::class);

    $url_assembler = $this->getMockBuilder(UnroutedUrlAssemblerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $url_assembler->method('assemble')->willReturnCallback(function($uri) {
      return (new GeneratedUrl())->setGeneratedUrl($uri);
    });

    $container = new ContainerBuilder();
    $container->set('search_api.data_type_helper', $dataTypeHelper);
    $container->set('plugin.manager.element_info', $elementInfo);
    $container->set('search_api.fields_helper', $fieldsHelper);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('unrouted_url_assembler', $url_assembler);
    \Drupal::setContainer($container);

    $index = $this->createMock(IndexInterface::class);
    $index->method('getFields')->willReturn([]);
    $config = [
      'fields' => ['foo'],
      'tags' => array_combine($tags, array_fill(0, count($tags), 5)),
      '#index' => $index,
    ];

    $plugin = StripTags::create($container, $config, 'strip_tags', []);
    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildConfigurationForm($form, $form_state);
    $this->assertFalse($form['title']['#access']);
    $this->assertFalse($form['alt']['#access']);

    $field = $this->createMock(FieldInterface::class);
    $field->method('isHidden')->willReturn(FALSE);
    $field->method('getType')->willReturn('text');

    $result = NULL;
    $fieldValues = [$original];
    $field->method('getValues')->willReturnReference($fieldValues);
    $field->method('setValues')
      ->willReturnCallback(function(array $values) use (&$result) {
        $result = $values;
      });

    $item = $this->createMock(ItemInterface::class);
    $fields = ['foo' => $field];
    $item->method('getFields')->willReturnReference($fields);
    $plugin->preprocessIndexItems([$item]);

    $this->assertEquals($expected, $result[0]);
  }

}
