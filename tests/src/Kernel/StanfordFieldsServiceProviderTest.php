<?php

namespace Drupal\Tests\stanford_fields\Kernel;

use Drupal\stanford_fields\Service\StanfordFieldsBookManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the service provider registers and alters services.
 */
#[Group('stanford_fields')]
#[RunTestsInSeparateProcesses]
class StanfordFieldsServiceProviderTest extends StanfordFieldKernelTestBase {

  /**
   * Book manager service gets overridden.
   */
  public function testService(){
    $this->assertFalse(\Drupal::hasService('book.manager'));
    \Drupal::service('module_installer')->install(['book']);

    $service = \Drupal::service('book.manager');
    $this->assertInstanceOf(StanfordFieldsBookManager::class, $service);
  }

}
