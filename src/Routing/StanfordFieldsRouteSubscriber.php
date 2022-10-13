<?php

namespace Drupal\stanford_fields\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class StanfordFieldsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('book.admin_edit')) {
      $route->setDefault('_form', '\Drupal\stanford_fields\Form\StanfordFieldBookAdminEditForm');
    }
  }

}
