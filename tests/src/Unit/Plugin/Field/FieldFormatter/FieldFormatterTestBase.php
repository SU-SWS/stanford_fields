<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for field formatter unit tests with mocked path services.
 */
abstract class FieldFormatterTestBase extends UnitTestCase {

  /**
   * Service container with the mocked services.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * Value the mocked path matcher returns for isFrontPage().
   *
   * @var bool
   */
  protected $isFrontPage = FALSE;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $path_matcher = $this->createMock(PathMatcherInterface::class);
    $path_matcher->method('isFrontPage')
      ->willReturnCallback([$this, 'isFrontPageCallback']);

    $path_validator = $this->createMock(PathValidatorInterface::class);

    $link_generator = $this->createMock(LinkGeneratorInterface::class);

    $link_generator->method('generate')
      ->willReturnCallback([$this, 'generateLink']);

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('path.matcher', $path_matcher);
    $this->container->set('path.validator', $path_validator);
    $this->container->set('link_generator', $link_generator);
    \Drupal::setContainer($this->container);
  }

  /**
   * Path matcher mock callback for isFrontPage().
   */
  public function isFrontPageCallback() {
    return $this->isFrontPage;
  }

  /**
   * Link generator mock callback that returns a fixed link.
   */
  public function generateLink($text, Url $url) {
    return '<a href="/foo-bar">Foo Bar</a>';
  }

}
