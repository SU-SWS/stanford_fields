<?php

namespace Drupal\Tests\stanford_fields\Kernel\Routing;

use Drupal\stanford_fields\Form\StanfordFieldBookAdminEditForm;
use Drupal\Tests\stanford_fields\Kernel\StanfordFieldKernelTestBase;

/**
 * Route subscriber modifies routes.
 *
 * @coversDefaultClass \Drupal\stanford_fields\Routing\StanfordFieldsRouteSubscriber
 */
class StanfordFieldsRouteSubscriberTest extends StanfordFieldKernelTestBase {

  /**
   * Book module routes are altered.
   */
  public function testBookRoute() {
    \Drupal::service('module_installer')->install(['book']);

    /** @var \Symfony\Component\Routing\Route $route */
    $route = \Drupal::service('router.route_provider')
      ->getRouteByName('book.admin_edit');


    $this->assertStringContainsString(StanfordFieldBookAdminEditForm::class,$route->getDefault('_form'));
  }

}
