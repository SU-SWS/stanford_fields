<?php

namespace Drupal\stanford_fields;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Stanford fields service provider.
 */
class StanfordFieldsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritDoc}
   */
  public function register(ContainerBuilder $container) {
    // Decorate the book manager service if it's available.
    if ($container->has('book.manager')) {
      $container->register('stanford_fields.book_manager', 'Drupal\stanford_fields\StanfordFieldsBookManager')
        ->setDecoratedService('book.manager')
        ->addArgument(new Reference('stanford_fields.book_manager.inner'))
        ->addArgument(new Reference('state'))
        ->addArgument(new Reference('config.factory'))
        ->setPublic(false);
    }
  }

}
