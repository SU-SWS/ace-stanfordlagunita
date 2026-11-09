<?php

namespace Drupal\sul_helper\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\sul_helper\Plugin\paragraphs\Behavior\SuCardStylesBehaviors;
use Drupal\sul_helper\SulServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * SUL Helper hooks.
 */
class SulHooks {

  use StringTranslationTrait;

  /**
   * Hooks constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user account.
   * @param \Drupal\sul_helper\SulServiceInterface $sulService
   *   SUL helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    #[Autowire(service: 'sul_helper.sul_service')]
    protected SulServiceInterface $sulService,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_paragraphs_behavior_info_alter().
   */
  #[Hook('paragraphs_behavior_info_alter')]
  public function paragraphsBehaviorInfoAlter(array &$paragraphs_behavior): void {
    $paragraphs_behavior['su_card_styles']['class'] = SuCardStylesBehaviors::class;
  }

  /**
   * Implements hook_ENTITY_TYPE_create_access().
   */
  #[Hook('paragraph_create_access')]
  public function paragraphCreateAccess(AccountInterface $account, array $context, ?string $entity_bundle): AccessResultInterface {
    return AccessResult::forbiddenIf($entity_bundle == 'stanford_gallery');
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension == 'layout_paragraphs') {
      $libraries['builder']['dependencies'][] = 'sul_helper/layout_paragraphs';
    }
    if ($extension == 'node') {
      $libraries['drupal.node']['dependencies'][] = 'sul_helper/node_form';
    }
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_single_element_viewfield_select_form_alter')]
  public function viewfieldSelectWidgetAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $element['view_options']['arguments']['#title'] = $this->t('Filtering Arguments (taxonomy terms)');
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_paragraph__stanford_spacer')]
  public function preprocessSpacerParagraph(array &$variables): void {
    // Copied from stanford_basic theme.
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['elements']['#paragraph'];
    if ($paragraph->hasField('su_spacer_size') && !$paragraph->get('su_spacer_size')->isEmpty()) {
      $variables['attributes']['class'][] = $paragraph->get('su_spacer_size')->getString();
    }
  }

  /**
   * Implements hook_migrate_prepare_row().
   *
   * On the person importer, we have a process plugin that sets the libcal and
   * libguide id numbers for each piece of content. Since that data comes from
   * a source other than the migration itself, we have to add some cache
   * invalidation mechanism so that the content will be updated on the next
   * migration run. This could be a new calendar or guide. By setting the
   * source data with the value, the migration module will use that to
   * generate the hash of the source data. If the guides or the calendar
   * change, a new hash will be created, and in turn will update the piece of
   * content.
   */
  #[Hook('migrate_prepare_row')]
  public function migratePrepareRow(Row $row, MigrateSourceInterface $source, MigrationInterface $migration): void {
    if ($migration->id() != 'su_stanford_person') {
      return;
    }
    $source_data = $row->getSource();
    $emails = [
      $source_data['sunetid'] . '@stanford.edu',
      $source_data['alias'] . '@stanford.edu',
      $source_data['sunetid'] . '@law.stanford.edu',
      $source_data['alias'] . '@law.stanford.edu',
    ];

    $guides = array_filter($this->sulService->getLibGuides(), fn($guide) => in_array($guide['owner']['email'], $emails));
    // Use only the following properties to determine if the cache is
    // different.
    $guides = array_map(fn($guide) => [
      'id' => $guide['id'],
      'name' => $guide['name'],
      'friendly_url' => $guide['friendly_url'],
    ], $guides);
    uasort($guides, fn($a, $b) => $a['id'] < $b['id'] ? -1 : 1);
    $row->setSourceProperty('libguides', array_values($guides));

    $calendars = array_filter($this->sulService->getLibCalUsers(), fn($cal_user) => in_array($cal_user['email'], $emails));
    // Calendars don't have any data that could update, so only need the ID.
    $calendars = array_map(fn($cal) => $cal['id'], $calendars);
    asort($calendars);
    $row->setSourceProperty('libcal', array_values($calendars));
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    // Hide body field for stanford_page and stanford_news content types.
    if ($operation == 'edit' && $field_definition->getName() == 'body') {
      $entity = $items?->getEntity();
      if (
        $entity &&
        $entity->getEntityTypeId() == 'node' &&
        in_array($entity->bundle(), ['stanford_page', 'stanford_news'])
      ) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function nodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_state->getFormObject()->getEntity();
    if (!$node->hasField('layout_selection')) {
      return;
    }
    if ($node->bundle() == 'stanford_page') {
      // Need to handle the basic page differently due to
      // stanford_profile_helper hooks moving things around.
      $form['#process'][] = [self::class, 'basicPageNodeFormProcess'];
    }
    $form['#process'][] = [self::class, 'nodeFormProcess'];
  }

  /**
   * Process callback to adjust node form rendering.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Modified form.
   */
  public static function nodeFormProcess(array $form, FormStateInterface $form_state): array {
    $form['advanced']['#type'] = 'vertical_tabs';
    $form['#attached']['library'][] = 'stanford_layout_paragraphs/layout_paragraphs';

    if (isset($form['meta'])) {
      $form['meta']['#type'] = 'details';
      $form['meta']['#title'] = new TranslatableMarkup('Publishing Information');
    }
    return $form;
  }

  /**
   * Process callback to adjust the basic page node form rendering.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Modified form.
   */
  public static function basicPageNodeFormProcess(array $form, FormStateInterface $form_state): array {
    $form['layout_selection']['rel_links'] = [
      '#type' => 'container',
      'sul_rel_links' => $form['sul_rel_links'],
      'sul_rel_links_heading' => $form['sul_rel_links_heading'],
      '#states' => [
        'visible' => [
          ':input[name="layout_selection"]' => ['value' => 'sul_side_nav'],
        ],
      ],
    ];
    unset($form['sul_rel_links'], $form['sul_rel_links_heading']);
    return $form;
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_layout_library_icons_form_alter')]
  public function layoutLibraryIconsWidgetAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    foreach (array_keys($field_widget_complete_form['widget']['#options']) as $layout_id) {
      $config = $this->configFactory->get("layout_library.layout.$layout_id");
      if ($config->get('id') && !$config->get('status')) {
        unset($field_widget_complete_form['widget']['#options'][$layout_id]);
      }
    }
  }

  /**
   * Modify the layout paragraph component form.
   */
  #[Hook('form_layout_paragraphs_component_form_alter')]
  public function layoutParagraphComponentFormAlter(array &$form, FormStateInterface $formState): void {
    $paragraph = $formState->getFormObject()->getParagraph();
    if ($paragraph->bundle() == 'sul_contact_card') {
      $state = [
        'visible' => ['[name="sul_contact__branch"]' => ['value' => '_none']],
      ];
      $form['sul_contact__email']['#states'] = $state;
      $form['sul_contact__hours']['#states'] = $state;
      $form['sul_contact__image']['#states'] = $state;
      $form['sul_contact__link']['#states'] = $state;
      $form['sul_contact__phone']['#states'] = $state;
      $form['sul_contact__title']['#states'] = $state;
      $form['sul_contact__address']['#states'] = $state;
      $form['sul_contact__map_link']['#states'] = $state;
    }
  }

  /**
   * Implements hook_field_widget_complete_form_alter().
   */
  #[Hook('field_widget_complete_form_alter')]
  public function onWidgetFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    $field_name = $context['items']->getName();
    if ($field_name == 'sul_contact__branch') {
      $field_widget_complete_form['widget']['#element_validate'][] = [
        self::class,
        'validateBranchField',
      ];
    }
    if ($field_name == 'layout_selection') {
      $field_widget_complete_form['widget']['#options']['_none'] = $this->t('Main navigation sidebar');
    }
  }

  /**
   * Validate the branch field for contact card paragraphs.
   *
   * @param array $element
   *   Field widget form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete entity form.
   */
  public static function validateBranchField(array $element, FormStateInterface $form_state, array $complete_form): void {
    /** @var \Drupal\layout_paragraphs\Form\EditComponentForm $object */
    $object = $form_state->getBuildInfo()['callback_object'];
    $value = $form_state->getValue($element['#parents']);
    $parent_entity = $object->getParagraph()->getParentEntity();
    foreach ($value as $target) {
      if ($parent_entity && $target['target_id'] == $parent_entity->id()) {
        $form_state->setError($element, 'This would create circular reference. Please create custom contact information.');
      }
    }
  }

  /**
   * Implements hook_viewfield_argument_suggestion_vocabs_alter().
   */
  #[Hook('viewfield_argument_suggestion_vocabs_alter')]
  public function viewfieldArgVocabs(array &$vocabs, array $view): void {
    if ($view['view'] == 'sul_people') {
      $vocabs[] = 'stanford_person_types';
    }
    if ($view['view'] == 'sul_events') {
      $vocabs[] = 'stanford_event_types';
      $vocabs[] = 'event_audience';
    }
  }

}
