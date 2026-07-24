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

  /**
   * Implements hook_preprocess_HOOK() for the faux course card preview.
   */
  #[Hook('preprocess_paragraph__csp_faux_course_card')]
  public function preprocessFauxCourseCard(array &$variables): void {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];
    $color = $paragraph->get('csp_course_card_color')->color;
    $variables['course_card_color_hex'] = $color ? '#' . ltrim((string) $color, '#') : '#e5e1d8';

    // Plain values rather than the rendered fields: the field templates wrap
    // their output in a div, which is not valid inside the component's
    // heading. Empty fields give NULL, which the component's string props
    // reject, so fall back to an empty string.
    $variables['course_card_title'] = $paragraph->get('csp_course_card_title')->value ?? '';
    $variables['course_card_format'] = $paragraph->get('csp_course_card_format')->value ?? '';
    $variables['course_card_location'] = $paragraph->get('csp_course_card_location')->value ?? '';

    $link_item = $paragraph->get('csp_course_card_link')->first();
    $variables['course_card_url'] = $link_item ? $link_item->getUrl()->toString() : '';
  }

  /**
   * Implements hook_preprocess_HOOK() for the course card instructor preview.
   */
  #[Hook('preprocess_paragraph__csp_course_card_instructor')]
  public function preprocessInstructor(array &$variables): void {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];
    // Empty fields give NULL, which the component's string props reject.
    $variables['instructor_name'] = $paragraph->get('csp_instructor_name')->value ?? '';
    $variables['instructor_title'] = $paragraph->get('csp_instructor_title')->value ?? '';

    $url_item = $paragraph->get('csp_instructor_url')->first();
    $variables['instructor_url'] = $url_item ? $url_item->getUrl()->toString() : '';
  }

}
