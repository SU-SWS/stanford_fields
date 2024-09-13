<?php

namespace Drupal\stanford_fields\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Menu link item constraint.
 */
class RelativeLinkFieldItemConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Current request.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $request;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'));
  }

  /**
   * Validation constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    $link_uri = $value->get(0)?->get('uri')?->getString();
    if ($link_uri && str_contains($link_uri, $this->request->getSchemeAndHttpHost())) {
      $this->context->addViolation($constraint->absoluteLink);
    }
  }

}
