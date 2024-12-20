<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Summer Entity type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "summer_entity_type",
 *   label = @Translation("Summer Entity type"),
 *   label_collection = @Translation("Summer Entity types"),
 *   label_singular = @Translation("summer entity type"),
 *   label_plural = @Translation("summer entities types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count summer entities type",
 *     plural = "@count summer entities types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\summer_helper\Form\SummerTypeForm",
 *       "edit" = "Drupal\summer_helper\Form\SummerTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\summer_helper\SummerTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer summer entity types",
 *   bundle_of = "summer_entity",
 *   config_prefix = "summer_entity_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/summer_entity_types/add",
 *     "edit-form" = "/admin/structure/summer_entity_types/manage/{summer_entity_type}",
 *     "delete-form" = "/admin/structure/summer_entity_types/manage/{summer_entity_type}/delete",
 *     "collection" = "/admin/structure/summer_entity_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class SummerType extends ConfigEntityBundleBase {

  /**
   * The machine name of this summer entity type.
   */
  protected string $id;

  /**
   * The human-readable name of the summer entity type.
   */
  protected string $label;

}
