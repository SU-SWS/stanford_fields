<?php

namespace Drupal\Tests\stanford_fields\Kernel\Hook;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;
use PHPUnit\Framework\Attributes\TestWith;

class StanfordFieldsHooksTest extends StanfordFieldKernelTestBase {

  #[TestWith(['link', TRUE])]
  #[TestWith(['text', FALSE])]
  public function testFieldConfigForm($field_type, $field_has_key) {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'type' => $field_type,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'bundle' => 'page',
      'label' => 'Field',
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder */
    $form_builder = $this->container->get('entity.form_builder');
    $field_form = $form_builder->getForm($field);
    if ($field_has_key) {
      $this->assertArrayHasKey('force_relative', $field_form['settings']);
    }
    else {
      $this->assertArrayNotHasKey('force_relative', $field_form['settings']);
    }
  }

}
