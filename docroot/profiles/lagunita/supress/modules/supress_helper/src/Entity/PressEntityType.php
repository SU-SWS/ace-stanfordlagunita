<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Press Entity type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "press_type",
 *   label = @Translation("Book Data type"),
 *   label_collection = @Translation("Book Data types"),
 *   label_singular = @Translation("Book Data type"),
 *   label_plural = @Translation("Book Data types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Book Data type",
 *     plural = "@count Book Data types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\supress_helper\Form\PressEntityTypeForm",
 *       "edit" = "Drupal\supress_helper\Form\PressEntityTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\supress_helper\PressEntityTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer press types",
 *   bundle_of = "press",
 *   config_prefix = "press_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/book-data/add",
 *     "edit-form" = "/admin/structure/book-data/manage/{press_type}",
 *     "delete-form" = "/admin/structure/book-data/manage/{press_type}/delete",
 *     "collection" = "/admin/structure/book-data",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "uuid",
 *   },
 * )
 */
final class PressEntityType extends ConfigEntityBundleBase {

  /**
   * The machine name of this Book Data type.
   */
  protected string $id;

  /**
   * The human-readable name of the Book Data type.
   */
  protected string $label;

  /**
   * A brief description of this entity type.
   *
   * @var string|null
   */
  protected $description = NULL;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description ?? '';
  }

}
