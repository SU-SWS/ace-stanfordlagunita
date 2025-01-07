<?php

namespace Drupal\summer_helper\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Global message constraint for published dates.
 */
class UniqueGlobalMessageConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * {@inheritDoc}
   */
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
    $publish_date = (int) $value->get('publish_on')->getString();
    $unpublish_date = (int) $value->get('unpublish_on')->getString();

    // If no publish or unpublish date configured.
    if (!$publish_date && !$unpublish_date && $this->isAnyPublished($value->id())) {
      $this->context->addViolation($constraint->invalidatePublishedDate);
      return;
    }

    // If published date is configured but not unpublished date.
    if ($publish_date && !$unpublish_date && $this->isPublishDateValid($publish_date, $value->id())) {
      $this->context->addViolation($constraint->invalidatePublishedDate);
      return;
    }

    // If unpublished date is configured but not published date.
    if (!$publish_date && $unpublish_date && $this->isUnpublishDateValid($unpublish_date, $value->id())) {
      $this->context->addViolation($constraint->invalidatePublishedDate);
      return;
    }

    // If unpublished date is configured but not published date.
    if ($publish_date && $unpublish_date && $this->isBothDatesValid($publish_date, $unpublish_date, $value->id())) {
      $this->context->addViolation($constraint->invalidatePublishedDate);
    }
  }

  /**
   * Check if any entity is currently published or is scheduled to be published.
   *
   * @param int|null $exclude_id
   *   Entity id to exclude from queries.
   *
   * @return bool
   *   True if any entity conflicts.
   */
  protected function isAnyPublished(int $exclude_id = NULL): bool {
    $query = $this->getEntityQuery($exclude_id);
    $condition_group = $query->orConditionGroup();
    $condition_group->condition('status', TRUE)
      ->condition('publish_on', NULL, 'IS NOT NULL');

    $query->condition($condition_group);
    return !empty($query->execute());
  }

  /**
   * Check if any entity will conflict with a message scheduled to publish.
   *
   * @param int $publish_date
   *   Publishing time stamp.
   * @param int|null $exclude_id
   *   Entity id to exclude from queries.
   *
   * @return bool
   *   True if any entity conflicts.
   */
  protected function isPublishDateValid(int $publish_date, int $exclude_id = NULL): bool {
    $query = $this->getEntityQuery($exclude_id);

    // A message is currently published and won't be unpublished.
    $first_condition_group = $query->andConditionGroup()
      ->condition('status', TRUE)
      ->condition('unpublish_on', NULL, 'IS NULL');

    // A message is unpublished and will be published after the published date.
    $second_condition_group = $query->orConditionGroup()
      ->condition('publish_on', $publish_date, '>')
      ->condition('unpublish_on', $publish_date, '>');

    $query->condition($query->orConditionGroup()
      ->condition($first_condition_group)
      ->condition($second_condition_group));
    return !empty($query->execute());
  }

  /**
   * Check if any entity will conflict with a message scheduled to unpublish.
   *
   * @param int $unpublish_date
   *   Unpublishing time stamp.
   * @param int|null $exclude_id
   *   Entity id to exclude from queries.
   *
   * @return bool
   *   True if any entity conflicts.
   */
  protected function isUnpublishDateValid(int $unpublish_date, int $exclude_id = NULL): bool {
    $query = $this->getEntityQuery($exclude_id);

    // A message is currently published.
    $first_condition_group = $query->andConditionGroup()
      ->condition('status', TRUE);

    // A message will be published before the unpublish date.
    $second_condition_group = $query->andConditionGroup()
      ->condition('publish_on', $unpublish_date, '<');

    $query->condition($query->orConditionGroup()
      ->condition($first_condition_group)
      ->condition($second_condition_group));

    return !empty($query->execute());
  }

  /**
   * Check for conflicts with a message scheduled to publish & unpublish.
   *
   * @param int $publish_date
   *   Publishing time stamp.
   * @param int $unpublish_date
   *   Unpublishing time stamp.
   * @param int|null $exclude_id
   *   Entity id to exclude from queries.
   *
   * @return bool
   *   True if any entity conflicts.
   */
  protected function isBothDatesValid(int $publish_date, int $unpublish_date, int $exclude_id = NULL): bool {
    $query = $this->getEntityQuery($exclude_id);
    // A message is currently published and won't be unpublished.
    $first_condition_group = $query->andConditionGroup()
      ->condition('status', TRUE)
      ->condition('unpublish_on', NULL, 'IS NULL');

    // A message will be published after the published date.
    $second_condition_group = $query->andConditionGroup()
      ->condition('publish_on', $publish_date, '>')
      ->condition($query->orConditionGroup()
        ->condition('unpublish_on', $unpublish_date, '<')
        ->condition('unpublish_on', NULL, 'IS NULL'));

    // A message will be published before the unpublish date.
    $third_condition_group = $query->andConditionGroup()
      ->condition('publish_on', $publish_date, '>')
      ->condition('publish_on', $unpublish_date, '<');

    $query->condition($query->orConditionGroup()
      ->condition($first_condition_group)
      ->condition($second_condition_group)
      ->condition($third_condition_group));

    return !empty($query->execute());
  }

  /**
   * Get the entity query for the entity storage, excluding the id if provided.
   *
   * @param int|null $exclude_id
   *   Entity id to exclude from queries.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Entity query service.
   */
  protected function getEntityQuery(int $exclude_id = NULL): QueryInterface {
    $summer_storage = $this->entityTypeManager->getStorage('summer_entity');
    $query = $summer_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'global_msg');

    if ($exclude_id) {
      $query->condition('id', $exclude_id, '!=');
    }
    return $query;
  }

}
