<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\summer_helper\SummerInterface;

/**
 * Defines the summer entity class.
 *
 * @ContentEntityType(
 *   id = "summer_entity",
 *   label = @Translation("Summer Entity"),
 *   label_collection = @Translation("Summer Entities"),
 *   label_singular = @Translation("summer entity"),
 *   label_plural = @Translation("summer entities"),
 *   label_count = @PluralTranslation(
 *     singular = "@count summer entities",
 *     plural = "@count summer entities",
 *   ),
 *   constraints = {
 *     "UniqueGlobalMessage" = {}
 *   },
 *   bundle_label = @Translation("Summer Entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\summer_helper\SummerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\summer_helper\SummerAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\summer_helper\Form\SummerForm",
 *       "edit" = "Drupal\summer_helper\Form\SummerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\summer_helper\Routing\SummerHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "summer_entity",
 *   admin_permission = "administer summer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "published" = "status"
 *   },
 *   links = {
 *     "collection" = "/admin/content/summer-entity",
 *     "add-form" = "/admin/content/summer/add/{summer_entity_type}",
 *     "add-page" = "/admin/content/summer/add",
 *     "canonical" = "/admin/content/summer/{summer_entity}",
 *     "edit-form" = "/admin/content/summer/{summer_entity}",
 *     "delete-form" = "/admin/content/summer/{summer_entity}/delete",
 *     "delete-multiple-form" = "/admin/content/summer-entity/delete-multiple",
 *   },
 *   bundle_entity_type = "summer_entity_type",
 *   field_ui_base_route = "entity.summer_entity_type.edit_form",
 * )
 */
final class Summer extends ContentEntityBase implements SummerInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Published')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the summer entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the summer entity was last edited.'));

    return $fields;
  }

}
