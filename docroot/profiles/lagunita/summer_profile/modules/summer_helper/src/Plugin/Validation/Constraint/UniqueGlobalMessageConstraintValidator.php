<?php

namespace Drupal\summer_helper\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Global message constraint for published dates.
 */
class UniqueGlobalMessageConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Validator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritDoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    /** @var \Drupal\summer_helper\SummerInterface $value */
    $summer_storage = $this->entityTypeManager->getStorage('summer_entity');
    $query = $summer_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'global_msg');

    $query->condition($query->orConditionGroup()
      ->condition('status', TRUE)
      ->condition('publish_on', time(), '>'));
    $published_message = $query->execute();

    $publish_date = (int) $value->get('publish_on')->getString();
    $unpublish_date = (int) $value->get('unpublish_on')->getString();

    // When saving the current published message, no need to check the published
    // dates.
    if (!$publish_date && $published_message && $value->id() != reset($published_message)) {
      $this->context->addViolation($constraint->invalidatePublishedDate);
    }

    // If both "published on" and "unpublished on" dates are configured, validate no other messages occur during that time.
    if ($publish_date && $unpublish_date) {
      $query = $summer_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'global_msg');
      $query->condition($query->orConditionGroup()
        ->condition('publish_on', [$publish_date, $unpublish_date], 'between')
        ->condition('unpublish_on', [
          $publish_date,
          $unpublish_date,
        ], 'between'));

      $ids = $query->execute();
      if ($ids && (count($ids) > 1 || $value->id() != reset($ids))) {
        $this->context->addViolation($constraint->invalidatePublishedDate);
      }
      return;
    }

    // If only the "published on" date is configured, validate no other messages will occur after.
    if ($publish_date && !$unpublish_date) {
      // Another message will be published in the future.
      $query = $summer_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'global_msg');
      $query->condition($query->orConditionGroup()
        ->condition('publish_on', $publish_date, '>')
        ->condition('unpublish_on', $publish_date, '>'));

      $ids = $query->execute();
      if ($ids && (count($ids) > 1 || $value->id() != reset($ids))) {
        $this->context->addViolation($constraint->invalidatePublishedDate);
        return;
      }

      // Another message is scheduled to publish before this one, but is not
      // schedule to unpublish.
      $query = $summer_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'global_msg')
        ->condition('publish_on', $publish_date, '<')
        ->condition('unpublish_on', NULL, 'IS NULL');
      $ids = $query->execute();
      if ($ids && (count($ids) > 1 || $value->id() != reset($ids))) {
        $this->context->addViolation($constraint->invalidatePublishedDate);
        return;
      }

      // Another message is currently published, but isn't schedule to be
      // unpublished.
      $query = $summer_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'global_msg')
        ->condition('status', TRUE)
        ->condition('unpublish_on', NULL, 'IS NULL');
      $ids = $query->execute();
      if ($ids && (count($ids) > 1 || $value->id() != reset($ids))) {
        $this->context->addViolation($constraint->invalidatePublishedDate);
        return;
      }
      return;
    }

    // If only the "unpublished on" date is configured, validate no other messages will occur until that date.
    if (!$publish_date && $unpublish_date) {
      $query = $summer_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'global_msg');

      $query->condition($query->orConditionGroup()
        ->condition('publish_on', $unpublish_date, '<')
        ->condition('status', TRUE));
      $ids = $query->execute();

      if ($ids && (count($ids) > 1 || $value->id() != reset($ids))) {
        $this->context->addViolation($constraint->invalidatePublishedDate);
      }
      return;
    }

    $query = $summer_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'global_msg');

    $query->condition($query->orConditionGroup()
      ->condition('status', TRUE)
      ->condition('publish_on', time(), '>'));
    $ids = $query->execute();

    // When saving the current published message, no need to check the published
    // dates.
    if ($ids && (count($ids) > 1 || $value->id() != reset($ids))) {
      $this->context->addViolation($constraint->invalidatePublishedDate);
    }
  }

}
