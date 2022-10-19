<?php

namespace Drupal\Tests\stanford_fields\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

class StanfordFieldKernelTestBase extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'path_alias',
    'node',
    'user',
    'datetime',
    'datetime_range',
    'stanford_fields',
    'field',
    'link',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('date_format');
    $this->installConfig(['system', 'field', 'link']);
    $this->installSchema('node', ['node_access']);

    NodeType::create(['type' => 'page'])->save();
    Role::create(['id' => RoleInterface::ANONYMOUS_ID])->save();
    Role::create(['id' => RoleInterface::AUTHENTICATED_ID])->save();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access content']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access content']);
  }

}
