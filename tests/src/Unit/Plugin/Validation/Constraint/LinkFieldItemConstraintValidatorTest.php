<?php

namespace Drupal\Tests\stanford_fields\Unit\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Validation\ExecutionContext;
use Drupal\Core\Validation\TranslatorInterface;
use Drupal\stanford_fields\Plugin\Validation\Constraint\RelativeLinkFieldItemConstraint;
use Drupal\stanford_fields\Plugin\Validation\Constraint\RelativeLinkFieldItemConstraintValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Test link field validation.
 */
class LinkFieldItemConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests the validate method.
   *
   * @testWith ["http://localhost", "http://localhost/foo/bar", true]
   *           ["http://localhost", "/foo/bar", false]
   *           ["http://localhost", "http://hostlocal/foo/bar", false]
   */
  #[TestWith(['http://localhost', 'http://localhost/foo/bar', TRUE])]
  #[TestWith(['http://localhost', '/foo/bar', FALSE])]
  #[TestWith(['http://localhost', 'http://hostlocal/foo/bar', FALSE])]
  public function testValidation($currentDomain, $linkUrl, $shouldHaveViolations) {
    // Create mocks for the services and dependencies.
    $request_stack = $this->createMock(RequestStack::class);

    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item = $this->createMock(FieldItemInterface::class);
    $request = $this->createMock(Request::class);

    // Configure the request mock to return a specific scheme and host.
    $request->method('getSchemeAndHttpHost')->willReturn($currentDomain);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    // Configure the field item list mock to return a field item with a specific URI.
    $field_item->method('get')->willReturnSelf();
    $field_item->method('getString')->willReturn($linkUrl);
    $field_item_list->method('get')->with(0)->willReturn($field_item);

    $container = new Container();
    $container->set('request_stack', $request_stack);

    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    $translator->method('trans')->willReturnCallback(fn($message) => $message);
    $context = new ExecutionContext($validator, NULL, $translator);

    // Instantiate the validator and set the context.
    $validator = TestRelativeLinkValidator::create($container);
    $validator->initialize($context);

    $constraint = new RelativeLinkFieldItemConstraint();
    $context->setConstraint($constraint);

    // Call the validate method.
    $validator->validate($field_item_list, $constraint);
    if ($shouldHaveViolations) {
      $this->assertTrue($validator->hasViolation());
    }
    else {
      $this->assertFalse($validator->hasViolation());
    }
  }

}

class TestRelativeLinkValidator extends RelativeLinkFieldItemConstraintValidator {

  public function hasViolation() {
    return count($this->context->getViolations()) > 0;
  }

}
