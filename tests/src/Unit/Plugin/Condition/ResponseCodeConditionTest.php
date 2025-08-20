<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\Condition;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\stanford_fields\Plugin\Condition\ResponseCodeCondition;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResponseCodeConditionTest extends UnitTestCase {

  /**
   * @var \Drupal\stanford_fields\Plugin\Condition\ResponseCodeCondition
   */
  protected $plugin;

  protected $requestAttributes = [];

  public function setup(): void {
    parent::setUp();

    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')
      ->willReturnCallback([$this, 'getCurrentRequest']);

    $container = new ContainerBuilder();
    $container->set('request_stack', $request_stack);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $config = ['response_codes' => "403\n404"];
    $this->plugin = ResponseCodeCondition::create($container, $config, '', []);
  }

  public function testCondition() {
    $form = [];
    $form_state = new FormState();
    $this->assertNotEmpty($this->plugin->buildConfigurationForm($form, $form_state));
    $this->assertStringContainsString('Return true on', (string) $this->plugin->summary());
    $this->assertStringContainsString('403, 404', (string) $this->plugin->summary());

    $this->assertFalse($this->plugin->evaluate());

    $this->requestAttributes['exception'] = new HttpException(404, 'foo');
    $this->assertTrue($this->plugin->evaluate());

    $this->requestAttributes['exception'] = new HttpException(500, 'foo');
    $this->assertFalse($this->plugin->evaluate());

    $this->plugin->setConfig('response_codes', NULL);
    $this->assertTrue($this->plugin->evaluate());
  }

  public function getCurrentRequest() {
    $current_request = $this->createMock(Request::class);
    $current_request->attributes = new ParameterBag($this->requestAttributes);

    return $current_request;
  }

}
