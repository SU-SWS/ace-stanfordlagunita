<?php

declare(strict_types=1);

namespace Drupal\Tests\sul_helper\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\sul_helper\Hook\SulHooks;
use Drupal\sul_helper\Plugin\paragraphs\Behavior\SuCardStylesBehaviors;
use Drupal\sul_helper\SulServiceInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the SUL helper hooks.
 */
#[Group('sul_helper')]
#[CoversClass(SulHooks::class)]
class SulHooksTest extends UnitTestCase {

  /**
   * SUL service mock.
   *
   * @var \Drupal\sul_helper\SulServiceInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sulService;

  /**
   * Layout library config values keyed by layout id.
   *
   * @var array
   */
  protected array $layoutConfigs = [];

  /**
   * Hooks under test.
   *
   * @var \Drupal\sul_helper\Hook\SulHooks
   */
  protected SulHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->sulService = $this->createMock(SulServiceInterface::class);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturnCallback(function (string $name) {
      $values = $this->layoutConfigs[str_replace('layout_library.layout.', '', $name)] ?? [];
      $config = $this->createMock(ImmutableConfig::class);
      $config->method('get')->willReturnCallback(fn($key) => $values[$key] ?? NULL);
      return $config;
    });

    $this->hooks = new SulHooks(
      $this->createMock(AccountProxyInterface::class),
      $this->sulService,
      $config_factory,
    );
  }

  /**
   * Card style behavior class is replaced.
   */
  public function testParagraphsBehaviorInfoAlter(): void {
    $behaviors = ['su_card_styles' => ['class' => 'Foo']];
    $this->hooks->paragraphsBehaviorInfoAlter($behaviors);
    $this->assertEquals(SuCardStylesBehaviors::class, $behaviors['su_card_styles']['class']);
  }

  /**
   * Gallery paragraphs can't be created.
   */
  public function testParagraphCreateAccess(): void {
    $account = $this->createMock(AccountInterface::class);
    $this->assertTrue($this->hooks->paragraphCreateAccess($account, [], 'stanford_gallery')->isForbidden());
    $this->assertFalse($this->hooks->paragraphCreateAccess($account, [], 'stanford_card')->isForbidden());
  }

  /**
   * Library dependencies are added.
   */
  public function testLibraryInfoAlter(): void {
    $libraries = ['builder' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'layout_paragraphs');
    $this->assertContains('sul_helper/layout_paragraphs', $libraries['builder']['dependencies']);

    $libraries = ['drupal.node' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'node');
    $this->assertContains('sul_helper/node_form', $libraries['drupal.node']['dependencies']);

    $libraries = ['foo' => []];
    $this->hooks->libraryInfoAlter($libraries, 'foo');
    $this->assertEquals(['foo' => []], $libraries);
  }

  /**
   * Viewfield arguments title changes.
   */
  public function testViewfieldSelectWidgetAlter(): void {
    $element = ['view_options' => ['arguments' => ['#title' => 'Arguments']]];
    $this->hooks->viewfieldSelectWidgetAlter($element, new FormState(), []);
    $this->assertEquals('Filtering Arguments (taxonomy terms)', (string) $element['view_options']['arguments']['#title']);
  }

  /**
   * Spacer paragraph gets the size class.
   */
  public function testPreprocessSpacerParagraph(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(FALSE);
    $field->method('getString')->willReturn('su-spacer-reduced');

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('hasField')->willReturn(TRUE);
    $paragraph->method('get')->willReturn($field);

    $variables = ['elements' => ['#paragraph' => $paragraph], 'attributes' => ['class' => []]];
    $this->hooks->preprocessSpacerParagraph($variables);
    $this->assertEquals(['su-spacer-reduced'], $variables['attributes']['class']);
  }

  /**
   * Person migration rows get libguide and libcal source properties.
   */
  public function testMigratePrepareRow(): void {
    $source = $this->createMock(MigrateSourceInterface::class);
    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('id')->willReturnOnConsecutiveCalls('foo', 'su_stanford_person');

    $row = new Row(['sunetid' => 'jdoe', 'alias' => 'john.doe']);
    $this->sulService->expects($this->once())->method('getLibGuides')->willReturn([
      ['id' => 5, 'name' => 'B', 'friendly_url' => 'b', 'owner' => ['email' => 'jdoe@stanford.edu'], 'extra' => 1],
      ['id' => 2, 'name' => 'A', 'friendly_url' => 'a', 'owner' => ['email' => 'john.doe@law.stanford.edu']],
      ['id' => 3, 'name' => 'C', 'friendly_url' => 'c', 'owner' => ['email' => 'other@stanford.edu']],
    ]);
    $this->sulService->expects($this->once())->method('getLibCalUsers')->willReturn([
      ['id' => 9, 'email' => 'jdoe@stanford.edu'],
      ['id' => 4, 'email' => 'john.doe@stanford.edu'],
      ['id' => 1, 'email' => 'other@stanford.edu'],
    ]);

    // Other migrations are ignored.
    $this->hooks->migratePrepareRow($row, $source, $migration);
    $this->assertNull($row->getSourceProperty('libguides'));

    $this->hooks->migratePrepareRow($row, $source, $migration);
    $this->assertEquals([
      ['id' => 2, 'name' => 'A', 'friendly_url' => 'a'],
      ['id' => 5, 'name' => 'B', 'friendly_url' => 'b'],
    ], $row->getSourceProperty('libguides'));
    $this->assertEquals([4, 9], $row->getSourceProperty('libcal'));
  }

  /**
   * Body field editing is forbidden on page and news nodes.
   */
  public function testEntityFieldAccess(): void {
    $account = $this->createMock(AccountInterface::class);
    $body_definition = $this->createMock(FieldDefinitionInterface::class);
    $body_definition->method('getName')->willReturn('body');

    $node = $this->createMock(ContentEntityInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('bundle')->willReturnOnConsecutiveCalls('stanford_page', 'stanford_event');
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $this->assertTrue($this->hooks->entityFieldAccess('edit', $body_definition, $account, $items)->isForbidden());
    $this->assertTrue($this->hooks->entityFieldAccess('edit', $body_definition, $account, $items)->isNeutral());
    $this->assertTrue($this->hooks->entityFieldAccess('view', $body_definition, $account, $items)->isNeutral());
    $this->assertTrue($this->hooks->entityFieldAccess('edit', $body_definition, $account)->isNeutral());
  }

  /**
   * Node forms get process callbacks.
   */
  public function testNodeFormAlter(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->willReturnOnConsecutiveCalls(FALSE, TRUE, TRUE);
    $node->method('bundle')->willReturnOnConsecutiveCalls('stanford_page', 'stanford_news');
    $form_object = $this->createMock(EntityFormInterface::class);
    $form_object->method('getEntity')->willReturn($node);
    $form_state = new FormState();
    $form_state->setFormObject($form_object);

    $form = [];
    $this->hooks->nodeFormAlter($form, $form_state, 'node_form');
    $this->assertEmpty($form);

    $this->hooks->nodeFormAlter($form, $form_state, 'node_form');
    $this->assertEquals([
      [SulHooks::class, 'basicPageNodeFormProcess'],
      [SulHooks::class, 'nodeFormProcess'],
    ], $form['#process']);

    $form = [];
    $this->hooks->nodeFormAlter($form, $form_state, 'node_form');
    $this->assertEquals([[SulHooks::class, 'nodeFormProcess']], $form['#process']);
  }

  /**
   * Node form process callbacks.
   */
  public function testNodeFormProcessCallbacks(): void {
    $form_state = new FormState();
    $form = SulHooks::nodeFormProcess(['meta' => []], $form_state);
    $this->assertEquals('vertical_tabs', $form['advanced']['#type']);
    $this->assertEquals('details', $form['meta']['#type']);
    $this->assertEquals('Publishing Information', (string) $form['meta']['#title']);
    $this->assertContains('stanford_layout_paragraphs/layout_paragraphs', $form['#attached']['library']);

    $form = SulHooks::basicPageNodeFormProcess([
      'sul_rel_links' => ['foo'],
      'sul_rel_links_heading' => ['bar'],
    ], $form_state);
    $this->assertArrayNotHasKey('sul_rel_links', $form);
    $this->assertArrayNotHasKey('sul_rel_links_heading', $form);
    $this->assertEquals(['foo'], $form['layout_selection']['rel_links']['sul_rel_links']);
    $this->assertEquals(['bar'], $form['layout_selection']['rel_links']['sul_rel_links_heading']);
  }

  /**
   * Disabled layout library layouts are removed from the widget.
   */
  public function testLayoutLibraryIconsWidgetAlter(): void {
    $this->layoutConfigs = [
      'disabled' => ['id' => 'disabled', 'status' => FALSE],
      'enabled' => ['id' => 'enabled', 'status' => TRUE],
    ];
    $element = ['widget' => ['#options' => ['disabled' => 'D', 'enabled' => 'E', 'other' => 'O']]];
    $this->hooks->layoutLibraryIconsWidgetAlter($element, new FormState(), []);
    $this->assertEquals(['enabled' => 'E', 'other' => 'O'], $element['widget']['#options']);
  }

  /**
   * Generic widget alter for the branch and layout selection fields.
   */
  public function testOnWidgetFormAlter(): void {
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturnOnConsecutiveCalls('sul_contact__branch', 'layout_selection');

    $element = ['widget' => []];
    $this->hooks->onWidgetFormAlter($element, new FormState(), ['items' => $items]);
    $this->assertEquals([[SulHooks::class, 'validateBranchField']], $element['widget']['#element_validate']);

    $element = ['widget' => ['#options' => ['_none' => '- None -']]];
    $this->hooks->onWidgetFormAlter($element, new FormState(), ['items' => $items]);
    $this->assertEquals('Main navigation sidebar', (string) $element['widget']['#options']['_none']);
  }

  /**
   * Viewfield vocabulary suggestions.
   */
  public function testViewfieldArgVocabs(): void {
    $vocabs = [];
    $this->hooks->viewfieldArgVocabs($vocabs, ['view' => 'sul_people']);
    $this->assertEquals(['stanford_person_types'], $vocabs);

    $vocabs = [];
    $this->hooks->viewfieldArgVocabs($vocabs, ['view' => 'sul_events']);
    $this->assertEquals(['stanford_event_types', 'event_audience'], $vocabs);
  }

}
