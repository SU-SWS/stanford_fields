<?php

namespace Drupal\stanford_fields\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that the submitted value is a unique integer.
 */
#[Constraint(
  id: 'relative_internal_link',
  label: new TranslatableMarkup('Relative Internal Link', [], ['context' => 'Validation'])
)]
class RelativeLinkFieldItemConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $absoluteLink = 'Please use relative links that start with "/" for paths on this site.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
