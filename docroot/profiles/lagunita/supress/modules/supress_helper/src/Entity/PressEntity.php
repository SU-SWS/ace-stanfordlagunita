<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\supress_helper\PressEntityInterface;

/**
 * Defines the Book Data entity class.
 *
 * @ContentEntityType(
 *   id = "press",
 *   label = @Translation("Book Data"),
 *   label_collection = @Translation("Book Data Entities"),
 *   label_singular = @Translation("Book Data Entity"),
 *   label_plural = @Translation("Book Data Entities"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Book Data Entity",
 *     plural = "@count Book Data Entities",
 *   ),
 *   bundle_label = @Translation("Book Data Entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\supress_helper\PressEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\supress_helper\PressAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\supress_helper\Form\PressEntityForm",
 *       "edit" = "Drupal\supress_helper\Form\PressEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "press",
 *   admin_permission = "administer press types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/book-data",
 *     "add-form" = "/admin/content/book-data/add/{press_type}",
 *     "add-page" = "/admin/content/book-data/add",
 *     "canonical" = "/admin/content/book-data/{press}",
 *     "edit-form" = "/admin/content/book-data/{press}/edit",
 *     "delete-form" = "/admin/content/book-data/{press}/delete",
 *     "delete-multiple-form" = "/admin/content/book-data/delete-multiple",
 *   },
 *   bundle_entity_type = "press_type",
 *   field_ui_base_route = "entity.press_type.edit_form",
 * )
 */
final class PressEntity extends ContentEntityBase implements PressEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
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

    $fields['work_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Work ID'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
