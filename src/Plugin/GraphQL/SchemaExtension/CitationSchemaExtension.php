<?php

declare(strict_types=1);

namespace Drupal\stanford_fields\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\ResolverOnlySchemaExtensionPluginBase;
use Drupal\stanford_publication\Entity\CitationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout Schema Extension.
 *
 * @codeCoverageIgnore
 *
 * @SchemaExtension(
 *   id = "stanford_citation_bibliography",
 *   name = "Stanford Decoupled Bibliography",
 *   description = @Translation("Layout entities"),
 *   schema = "graphql_compose",
 * )
 */
class CitationSchemaExtension extends ResolverOnlySchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('CitationInterface', 'apa',
      $builder->callback(fn(CitationInterface $citation) => $citation->getBibliography()),
    );
    $registry->addFieldResolver('CitationInterface', 'chicago',
      $builder->callback(fn(CitationInterface $citation) => $citation->getBibliography(CitationInterface::CHICAGO)),
    );
  }

}
