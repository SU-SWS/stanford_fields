<?php

namespace Drupal\stanford_fields;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

class StanfordFieldsServiceProvider extends ServiceProviderBase {

  public function register(ContainerBuilder $container) {
    if ($container->has('book.manager')) {
      $container->register('stanford_fields.book_manager', 'Drupal\stanford_fields\StanfordFieldsBookManager')
        ->setDecoratedService('book.manager')
        ->addArgument(new Reference('stanford_fields.book_manager.inner'))
        ->addArgument(new Reference('state'))
        ->setPublic(false);
    }
  }

}
