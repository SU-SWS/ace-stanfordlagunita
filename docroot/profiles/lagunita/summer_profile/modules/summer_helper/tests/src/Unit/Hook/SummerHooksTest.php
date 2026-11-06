<?php

declare(strict_types=1);

namespace Drupal\Tests\summer_helper\Unit\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\summer_helper\Hook\SummerHooks;
use Drupal\summer_helper\Plugin\paragraphs\Behavior\SummerCardBehaviors;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Summer helper hooks.
 */
#[Group('summer_helper')]
#[CoversClass(SummerHooks::class)]
class SummerHooksTest extends UnitTestCase {

  /**
   * Hooks under test.
   *
   * @var \Drupal\summer_helper\Hook\SummerHooks
   */
  protected SummerHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $module_list = $this->createMock(ModuleExtensionList::class);
    $module_list->method('getPath')->willReturn('profiles/summer_helper');

    $this->hooks = new SummerHooks(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(FileSystemInterface::class),
      $module_list,
    );
  }

  /**
   * Card style behavior class is replaced.
   */
  public function testParagraphsBehaviorInfoAlter(): void {
    $behaviors = ['su_card_styles' => ['class' => 'Foo']];
    $this->hooks->paragraphsBehaviorInfoAlter($behaviors);
    $this->assertEquals(SummerCardBehaviors::class, $behaviors['su_card_styles']['class']);
  }

  /**
   * Admin library is attached to pages.
   */
  public function testPageAttachments(): void {
    $attachments = [];
    $this->hooks->pageAttachments($attachments);
    $this->assertEquals(['summer_helper/admin'], $attachments['#attached']['library']);
  }

  /**
   * CKEditor stylesheet library is replaced.
   */
  public function testLibraryInfoAlter(): void {
    $libraries = [];
    $this->hooks->libraryInfoAlter($libraries, 'node');
    $this->assertEmpty($libraries);

    $this->hooks->libraryInfoAlter($libraries, 'ckeditor5');
    $this->assertEquals(
      ['/profiles/summer_helper/styles/dist/css/ckeditor.css' => []],
      $libraries['internal.drupal.ckeditor5.stylesheets']['css']['theme']
    );
  }

  /**
   * Status is disabled when a publish date is set.
   */
  public function testSummerEntityFormAlter(): void {
    $form = ['status' => ['widget' => ['value' => []]]];
    $this->hooks->summerEntityFormAlter($form, new FormState(), 'summer_entity_form');
    $this->assertEquals(
      ['disabled' => [':input[name="publish_on[0][value][time]"]' => ['filled' => TRUE]]],
      $form['status']['widget']['value']['#states']
    );
  }

  /**
   * Paragraphs get a bundle class.
   */
  public function testPreprocessParagraph(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('sum_card');
    $variables = ['paragraph' => $paragraph, 'attributes' => ['class' => []]];
    $this->hooks->preprocessParagraph($variables);
    $this->assertEquals(['paragraph-sum-card'], $variables['attributes']['class']);
  }

  /**
   * Viewfield vocabulary suggestions.
   */
  public function testViewfieldArgVocabs(): void {
    $vocabs = [];
    $this->hooks->viewfieldArgVocabs($vocabs, ['view' => 'sum_courses']);
    $this->assertEquals(['sum_course_learner'], $vocabs);
  }

}
