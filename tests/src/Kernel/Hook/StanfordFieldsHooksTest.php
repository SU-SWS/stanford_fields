<?php

namespace Drupal\Tests\stanford_fields\Kernel\Hook;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\TestWith;

class StanfordFieldsHooksTest extends StanfordFieldKernelTestBase {

    #[TestWith(['link', TRUE])]
    #[TestWith(['text', FALSE])]
    public function testFieldConfigForm(string $field_type, bool $has_settings) {
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
      if ($has_settings) {
        $this->assertArrayHasKey('force_relative', $field_form['settings']);
      }
      else {
        $this->assertArrayNotHasKey('force_relative', $field_form['settings']);
      }
    }

    #[TestWith(['fontawesome_icon', TRUE])]
    #[TestWith(['text', FALSE])]
    public function testWidgetThirdPartySettings(string $field_type, bool $has_settings
    ) {
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

      if ($has_settings) {
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

}
