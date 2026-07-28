<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\csp_helper\Layouts\CspOneColumn;
use Drupal\csp_helper\Layouts\CspThreeColumn;
use Drupal\csp_helper\Layouts\CspTwoColumn;

/**
 * Hook implementations for the csp_helper module.
 */
class CspHelperHooks {

  /**
   * Implements hook_theme().
   *
   * Registers the Faux Course Card preview templates as suggestions of the
   * base 'paragraph' hook.
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__csp_faux_course_card' => [
        'base hook' => 'paragraph',
        'template' => 'paragraph--csp-faux-course-card',
      ],
      'paragraph__csp_course_card_instructor' => [
        'base hook' => 'paragraph',
        'template' => 'paragraph--csp-course-card-instructor',
      ],
    ];
  }

  /**
   * Implements hook_layout_alter().
   *
   * Swaps the upstream stanford_layout_paragraphs layout plugins for CSP
   * subclasses that add an "Ultra slim" (~6px) option to the "Space below
   * section" (bottom_margin) select. Overriding the plugin class keeps the
   * change scoped to the CSP site.
   */
  #[Hook('layout_alter')]
  public function layoutAlter(array &$definitions): void {
    $class_map = [
      'layout_paragraphs_1_column' => CspOneColumn::class,
      'layout_paragraphs_2_column' => CspTwoColumn::class,
      'layout_paragraphs_3_column' => CspThreeColumn::class,
    ];
    foreach ($class_map as $layout_id => $class) {
      if (isset($definitions[$layout_id])) {
        $definitions[$layout_id]->setClass($class);
      }
    }
  }

}
