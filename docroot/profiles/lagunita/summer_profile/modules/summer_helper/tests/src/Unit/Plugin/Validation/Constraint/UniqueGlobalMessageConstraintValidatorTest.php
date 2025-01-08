<?php

namespace Drupal\Tests\summer_helper\Unit\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\summer_helper\Plugin\Validation\Constraint\UniqueGlobalMessageConstraint;
use Drupal\summer_helper\Plugin\Validation\Constraint\UniqueGlobalMessageConstraintValidator;
use Drupal\summer_helper\SummerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \Drupal\summer_helper\Plugin\Validation\Constraint\UniqueGlobalMessageConstraintValidator
 */
class UniqueGlobalMessageConstraintValidatorTest extends UnitTestCase {

  /**
   * @var int[]
   */
  protected $entityQueryResults;

  /**
   * @var \Drupal\summer_helper\Plugin\Validation\Constraint\UniqueGlobalMessageConstraintValidator
   */
  protected $validator;

  /**
   * @var \Drupal\summer_helper\SummerInterface
   */
  protected $messageEntity;

  /**
   * @var string
   */
  protected $publishDate;

  /**
   * @var string
   */
  protected $unpublishDate;

  protected function setUp(): void {
    parent::setUp();

    $condition_group = $this->createMock(ConditionInterface::class);
    $condition_group->method('condition')->willReturnSelf();

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('orConditionGroup')->willReturn($condition_group);
    $query->method('andConditionGroup')->willReturn($condition_group);
    $query->method('execute')->willReturnReference($this->entityQueryResults);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('getQuery')->wilLReturn($query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $this->validator = TestUniqueValidator::create($container);
    $this->validator->initialize($this->getContext());

    $this->messageEntity = $this->createMock(SummerInterface::class);
    $this->messageEntity->method('id')->willReturn(999);
    $this->messageEntity->method('get')
      ->will($this->returnCallback([$this, 'getMessageField']));
  }

  public static function dataProvider(): array {
    return [
      [NULL, NULL, [], FALSE],
      [123, NULL, [], FALSE],
      [NULL, 123, [], FALSE],
      [123, 123, [], FALSE],
      [NULL, NULL, [123, 234], TRUE],
      [123, NULL, [123, 234], TRUE],
      [NULL, 123, [123, 234], TRUE],
      [123, 123, [123, 234], TRUE],
    ];
  }

  /**
   * @dataProvider dataProvider
   */
  public function testValidations($publish_date, $unpublish_date, $query_results, $has_errors) {
    $constraint = new UniqueGlobalMessageConstraint();

    $this->entityQueryResults = $query_results;
    $this->publishDate = $publish_date;
    $this->unpublishDate = $unpublish_date;
    $this->validator->validate($this->messageEntity, $constraint);
    $this->assertEquals($has_errors, $this->validator->hasErrors());
  }

  public function getMessageField($field_name): FieldItemInterface {
    $field_results = $this->createMock(FieldItemInterface::class);
    switch ($field_name) {
      case 'publish_on':
        $field_results->method('getString')->willReturn($this->publishDate);
        break;

      case 'unpublish_on':
        $field_results->method('getString')->willReturn($this->unpublishDate);
        break;
    }
    return $field_results;
  }

  /**
   * Build a context object for the validator.
   *
   * @return \Symfony\Component\Validator\Context\ExecutionContext
   */
  protected function getContext() {
    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    return new ExecutionContext($validator, '', $translator);
  }

}

class TestUniqueValidator extends UniqueGlobalMessageConstraintValidator {

  /**
   * If the violation has errors.
   *
   * @return bool
   *   Violations exist.
   */
  public function hasErrors() {
    return $this->context->getViolations()->count() > 0;
  }

  public function clearErrors() {
    $this->context->addViolation();
  }

}
