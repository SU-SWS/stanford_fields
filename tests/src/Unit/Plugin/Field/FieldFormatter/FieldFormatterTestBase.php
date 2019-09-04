<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Link;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Tests\UnitTestCase;

abstract class FieldFormatterTestBase extends UnitTestCase {

  protected $container;

  protected $isFrontPage = FALSE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $path_matcher = $this->createMock(PathMatcherInterface::class);
    $path_matcher->method('isFrontPage')
      ->will($this->returnCallback([$this, 'isFrontPageCallback']));

    $path_validator = $this->createMock(PathValidatorInterface::class);

    $link_generator = $this->createMock(LinkGeneratorInterface::class);
    $link_generator->method('generateFromLink')
      ->will($this->returnCallback([$this, 'generateFromLinkCallback']));

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('path.matcher', $path_matcher);
    $this->container->set('path.validator', $path_validator);
    $this->container->set('link_generator', $link_generator);
    \Drupal::setContainer($this->container);
  }

  public function isFrontPageCallback() {
    return $this->isFrontPage;
  }

  public function generateFromLinkCallback(Link $link) {
    return '<a href="/foo-bar">Foo Bar</a>';
  }

}
