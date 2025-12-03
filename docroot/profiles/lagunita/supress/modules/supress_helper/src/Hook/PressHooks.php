<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * SuPress hooks.
 */
class PressHooks {

  #[Hook('entity_create')]
  public function pressCreate(EntityInterface $entity) {
    if (InstallerKernel::installationAttempted()) {
      return;
    }

    $migration = self::getBookMigration();
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

  protected static function getBookMigration(): ?MigrationInterface {
    return \Drupal::service('plugin.manager.migration')
      ->createInstance('sup_import_books') ?: NULL;
  }

  /**
   * Implements hook_viewfield_argument_suggestion_vocabs_alter().)
   */
  #[Hook('viewfield_argument_suggestion_vocabs_alter')]
  public function viewfieldArgVocabs(array &$vocabs, array $view) {
    if ($view['view'] == 'sup_books') {
      $vocabs[] = 'sup_book_subjects';
      $vocabs[] = 'sup_series';
      $vocabs[] = 'sup_book_tags';
      $vocabs[] = 'sup_imprints';
    }
  }

}
