<?php

namespace Drupal\Tests\stanford_fields\Kernel;

use Drupal\stanford_fields\Service\StanfordFieldsBookManager;

/**
 * Test the service provider registers and alters services.
 *
 * @coversDefaultClass \Drupal\stanford_fields\StanfordFieldsServiceProvider
 */
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
