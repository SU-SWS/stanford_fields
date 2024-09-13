<?php

namespace Drupal\stanford_fields\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 */
#[Constraint(
  id: 'relative_internal_link',
  label: new TranslatableMarkup('Relative Internal Link', [], ['context' => 'Validation'])
)]
class RelativeLinkFieldItemConstraint extends SymfonyConstraint {

  public $absoluteLink = 'Please use relative links that start with "/" for paths on this site.';

}
