<?php

namespace Drupal\Tests\stanford_fields\Kernel\Hook;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Form\FieldStorageAddForm;
use Drupal\node\Entity\Node;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Test hook functionality.
 */
class StanfordFieldsHooksTest extends StanfordFieldKernelTestBase {

  /**
   * Link field will get a constraint applied.
   */
  public function testLinkConstraint() {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'type' => 'link',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'bundle' => 'page',
      'label' => 'Field',
      'third_party_settings' => [
        'stanford_fields' => [
          'force_relative' => TRUE,
        ],
      ],
    ]);
    $field->save();
    $node = Node::create(['type' => 'page', 'title' => 'foo']);
    $field_constraints = $node->getFieldDefinition('field_foo')
      ->getConstraints();
    $this->assertArrayHasKey('relative_internal_link', $field_constraints);
  }

  /**
   * Field config form will have the settings for links.
   *
   * @param string $field_type
   *   Field type for storage creation.
   */
  #[TestWith(['link'])]
  #[TestWith(['text'])]
  public function testFieldConfigForm(string $field_type) {
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'type' => $field_type,
    ])->save();
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
    if ($field_type == 'link') {
      $this->assertArrayHasKey('force_relative', $field_form['settings']);
    }
    else {
      $this->assertArrayNotHasKey('force_relative', $field_form['settings']);
    }
  }

  #[TestWith(['fontawesome_icon'])]
  #[TestWith(['text'])]
  public function testWidgetThirdPartySettings(string $field_type) {
    $this->container->get('module_installer')->install(['fontawesome']);

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access fontawesome additional settings']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access fontawesome additional settings']);

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'type' => $field_type,
    ])->save();
    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_foo',
      'bundle' => 'page',
      'label' => 'Field',
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $form_display->setComponent('field_foo');
    $form_display->save();

    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder */
    $form_builder = $this->container->get('entity.form_builder');
    $node_display_form = $form_builder->getForm($form_display, 'edit', ['plugin_settings_edit' => 'field_foo']);

    if ($field_type == 'fontawesome_icon') {
      $this->assertArrayHasKey('hidden_settings', $node_display_form['fields']['field_foo']['plugin']['settings_edit_form']['third_party_settings']['stanford_fields']);
    }
    else {
      $this->assertArrayNotHasKey('hidden_settings', $node_display_form['fields']['field_foo']['plugin']['settings_edit_form']['third_party_settings']['stanford_fields']);
      return;
    }

    $form_display->setComponent('field_foo', [
      'type' => 'fontawesome_icon_widget',
      'third_party_settings' => [
        'stanford_fields' => [
          'hidden_settings' => [
            'iconset',
            'size',
            'border',
          ],
        ],
      ],
    ]);
    $form_display->save();

    $node = Node::create(['type' => 'page', 'title' => 'foo']);
    $node_form = $form_builder->getForm($node);

    $settings = [
      'style' => TRUE,
      'iconset' => FALSE,
      'size' => FALSE,
      'fixed-width' => TRUE,
      'border' => FALSE,
      'invert' => TRUE,
      'animation' => TRUE,
      'pull' => TRUE,
      'additional_classes' => TRUE,
      'duotone' => TRUE,
      'masking' => TRUE,
      'power_transforms' => TRUE,
    ];
    foreach ($settings as $setting => $access) {
      if ($access) {
        $this->assertArrayNotHasKey('#access', $node_form['field_foo']['widget'][0]['settings'][$setting], sprintf('%s should have access', $setting));
      }
      else {
        $this->assertFalse($node_form['field_foo']['widget'][0]['settings'][$setting]['#access'], sprintf('%s should not have access', $setting));
      }
    }
  }

  public function testFieldUiStorageFormAlter() {
    $form_state = new FormState();
    $form_state->set('entity_type_id', 'node');
    $form_state->set('bundle', 'page');
    $form_state->set('field_type', 'text');
    $form_state->set('display_as_group', FALSE);

    $form = $this->container->get('form_builder')
      ->buildForm(FieldStorageAddForm::class, $form_state);
    $this->assertEquals(33, $form['field_name']['#maxlength']);
  }

}
