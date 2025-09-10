<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Drupal\supress_helper\Plugin\paragraphs\Behavior\PressCardBehaviors;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * SuPress hooks.
 */
class PressHooks {

  use StringTranslationTrait;

  /**
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationPluginManager
   *   Migration plugin manager.
   */
  public function __construct(
    protected readonly MigrationPluginManagerInterface $migrationPluginManager,
    protected readonly ModuleExtensionList $moduleList,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'search_api_algolia.helper')]
    protected readonly SearchApiAlgoliaHelper $searchApiAlgoliaHelper,
  ) {}

  #[Hook('paragraphs_behavior_info_alter')]
  public function paragraphBehaviorsInfoAlter(array &$paragraphs_behavior) {
    $paragraphs_behavior['su_card_styles']['class'] = PressCardBehaviors::class;
  }

  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#attached']['library'][] = 'supress_helper/admin';
  }

  #[Hook('paragraph_create_access')]
  public function paragraphCreateAccess(AccountInterface $account, array $context, string $entity_bundle) {
    return AccessResult::forbiddenIf($entity_bundle == 'stanford_banner');
  }

  #[Hook('media_access')]
  public function mediaAccess(MediaInterface $media, $operation, AccountInterface $account) {
    $route = $this->routeMatch->getRouteName();
    return AccessResult::forbiddenIf($route != 'graphql.query.graphql_compose_server' && $media->bundle() == 'sup_protected_file' && $account->isAnonymous());
  }

  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node) {
    if (
      $node->hasField('sup_page_search_exclude') &&
      $node->get('sup_page_search_exclude')->getString()
    ) {
      $this->searchApiAlgoliaHelper->entityDelete($node);
    }
  }

  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'ckeditor5') {
      $module_path = $this->moduleList->getPath('supress_helper');
      $libraries['internal.drupal.ckeditor5.stylesheets'] = [
        'css' => [
          'theme' => [
            "/$module_path/styles/dist/css/ckeditor.css" => [],
          ],
        ],
      ];
    }
  }

  #[Hook('media_create')]
  #[Hook('press_create')]
  public function pressCreate(EntityInterface $entity) {
    if (InstallerKernel::installationAttempted()) {
      return;
    }

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('sup_import_books');
    if (!$migration) {
      return;
    }

    // If an award is created (only from the importer), set the node that
    // relates to the award to be updated on the next import.
    if ($entity->getEntityTypeId() == 'press') {
      $migration->getIdMap()->setUpdate([
        'work_id_number' => $entity->get('work_id')->getString(),
      ]);
    }

    // If an image is created , set the node that relates to the award to be
    // updated on the next import.
    if (
      $entity instanceof MediaInterface &&
      $entity->bundle() == 'image' &&
      $entity->get('sup_book_work_id')->count()
    ) {
      $migration->getIdMap()->setUpdate([
        'work_id_number' => $entity->get('sup_book_work_id')->getString(),
      ]);
    }
  }

  #[Hook('search_api_algolia_objects_alter')]
  public function searchApiAlgoliaObjectsAlter(array &$objects, IndexInterface $index, array $items) {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($objects as &$object) {
      if (isset($object['book_subject'])) {
        $object['book_subject'] = is_array($object['book_subject']) ? $object['book_subject'] : [$object['book_subject']];
        $all_subjects = [];

        foreach ($object['book_subject'] as &$term_id) {
          $term = $term_storage->load($term_id);
          $all_subjects[] = $term->label();
          while ($parent = $term_storage->loadParents($term_id)) {
            $term = reset($parent);
            $all_subjects[] = $term->label();
            $term_id = $term->id();
          }
          $term_id = $term->label();
        }

        asort($all_subjects);
        asort($object['book_subject']);
        $object['book_subject_all'] = $all_subjects;
        $object['book_subject'] = array_values(array_unique($object['book_subject']));
      }
    }
  }

  #[Hook('field_widget_complete_options_select_form_alter')]
  public function fieldSelectFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context) {
    if ($context['items']->getName() == 'sup_search_subject') {
      // Only show the top level terms and the "None" option.
      $field_widget_complete_form['widget']['#options'] = array_filter($field_widget_complete_form['widget']['#options'], fn($option) => !str_starts_with($option, '-') || $option == '- None -');
    }
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_single_element_viewfield_select_form_alter')]
  function fieldViewfeldFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    $element['view_options']['helper'] = [
      '#type' => 'container',
      'help_text' => [
        '#markup' => $this->t('Book view arguments should be constructed like %subject/%series/%tags/%imprints/%work. To skip an argument, provide an empty quotes: "".', [
          '%subject' => 'subject-term-names',
          '%series' => 'series-term-names',
          '%tags' => 'book-tag-names',
          '%imprints' => 'book-imprint-names',
          '%work' => 'work+id+numbers',
        ]),
      ],
      '#weight' => 21,
      '#states' => [
        'visible' => [
          '[name="su_list_view[0][display_id]"]' => [
            ['value' => 'award_winners'],
            ['value' => 'book_list'],
          ],
        ],
      ],
    ];

    $element['view_options']['seasonal_helper'] = [
      '#type' => 'container',
      'help_text' => [
        '#markup' => $this->t('The argument should be the exact string of the "Catalog Season" field'),
      ],
      '#weight' => 21,
      '#states' => [
        'visible' => [
          '[name="su_list_view[0][display_id]"]' => ['value' => 'seasonal_list'],
        ],
      ],
    ];
  }

}
