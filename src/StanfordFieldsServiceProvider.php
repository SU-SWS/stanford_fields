<?php

namespace Drupal\stanford_fields;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\stanford_fields\Service\StanfordFieldsBookManager;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Stanford fields service provider.
 */
class StanfordFieldsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritDoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the book manager service class if it's available.
    if ($container->hasDefinition('book.manager')) {
      $container->getDefinition('book.manager')
        ->setClass(StanfordFieldsBookManager::class)
        // The book module's generated proxy class only contains the methods on
        // the original class, so it can't be used with the overridden class.
        ->setLazy(FALSE)
        ->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
    }
  }

}
