<?/**
 * Implements hook_path_update().
 */
namespace Drupal\supress\Tests\Unit\Plugin\Migrate\Process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\supress\Plugin\migrate\process\NestedTermGenerate;
use Drupal\Tests\UnitTestCase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Tests the NestedTermGenerate plugin.
 *
 * @group supress
 */
class NestedTermGenerateTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * The NestedTermGenerate plugin.
   *
   * @var \Drupal\supress\Plugin\migrate\process\NestedTermGenerate
   */
  protected $plugin;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $termStorage;

  /**
   * The migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migrateExecutable;

  /**
   * The row.
   *
   * @var \Drupal\migrate\Row|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $row;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a mock term storage.
    $this->termStorage = $this->createMock(TermStorageInterface::class);

    // Create a mock entity type manager.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($this->termStorage);

    // Create a mock migrate executable.
    $this->migrateExecutable = $this->createMock(MigrateExecutableInterface::class);

    // Create a mock row.
    $this->row = $this->createMock(Row::class);

    // Create a container with the necessary services.
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Create an instance of the NestedTermGenerate plugin.
    $this->plugin = new NestedTermGenerate([], 'nested_term_generate', [], $this->getStringTranslationStub());
  }

  /**
   * Tests the transform() method.
   */
  public function testTransform() {
    // Create a mock term.
    $term = $this->createMock(Term::class);
    $term->method('id')->willReturn(123);

    // Configure the term storage to return the mock term.
    $this->termStorage->method('loadByProperties')->willReturn([$term]);

    // Set up the plugin configuration.
    $this->plugin->setConfiguration([
      'vocabulary' => 'tags',
      'delimiter' => ',',
    ]);

    // Set up the row value.
    $value = 'Term 1, Term 2, Term 3';
    $this->row->method('getSourceProperty')->willReturn($value);

    // Call the transform() method.
    $result = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');

    // Assert that the term storage was called with the correct properties.
    $this->termStorage->expects($this->exactly(3))
      ->method('loadByProperties')
      ->withConsecutive(
        [['name' => 'Term 1', 'vid' => 'tags']],
        [['name' => 'Term 2', 'vid' => 'tags']],
        [['name' => 'Term 3', 'vid' => 'tags']]
      );

    // Assert that the term was not created.
    $this->termStorage->expects($this->never())
      ->method('create');

    // Assert that the term was not saved.
    $term->expects($this->never())
      ->method('save');

    // Assert that the transform() method returns the correct parent term ID.
    $this->assertEquals(123, $result);
  }

}
