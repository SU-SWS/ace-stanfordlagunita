<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

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

}
