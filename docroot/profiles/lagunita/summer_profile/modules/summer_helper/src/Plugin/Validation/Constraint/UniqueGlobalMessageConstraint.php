<?php

namespace Drupal\summer_helper\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint as ConstraintAttribute;
use Symfony\Component\Validator\Constraint;

/**
 * Global message constraint for published dates.
 */
#[ConstraintAttribute(
  id: "UniqueGlobalMessage",
  label: new TranslatableMarkup("Unique Global Message Date"),
  type: "string"
)]
class UniqueGlobalMessageConstraint extends Constraint {

  /**
   * Error message.
   *
   * @var string
   */
  public $invalidatePublishedDate = 'The published dates are invalid. Only one message can be published at a time.';

}
