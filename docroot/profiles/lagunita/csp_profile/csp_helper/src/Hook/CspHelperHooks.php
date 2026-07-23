<?php

declare(strict_types=1);

namespace Drupal\csp_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the csp_helper module.
 */
class CspHelperHooks {

  /**
   * Color bar token approximations for the Drupal-side preview only.
   *
   * These are rough stand-ins for the real CSP design tokens (see CSP-105/
   * CSP-87) so editors get a sense of which bar they picked. The Next.js
   * frontend owns the real color values.
   */
  protected const COLOR_PREVIEW_HEX = [
    'lagunita' => '#00778B',
    'plum' => '#620059',
    'palo-verde' => '#175E54',
    'olive' => '#4F4821',
    'cardinal' => '#8C1515',
    'archway' => '#D2C295',
  ];

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
    $variables['course_card_color_hex'] = self::COLOR_PREVIEW_HEX[$paragraph->get('csp_course_card_color')->value ?? ''] ?? '#e5e1d8';
    $variables['course_card_format'] = $paragraph->get('csp_course_card_format')->value;
    $variables['course_card_location'] = $paragraph->get('csp_course_card_location')->value;
    $link_item = $paragraph->get('csp_course_card_link')->first();
    $variables['course_card_url'] = $link_item ? $link_item->getUrl()->toString() : '';

    $this->attachPreviewLibrary($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for the course card instructor preview.
   */
  #[Hook('preprocess_paragraph__csp_course_card_instructor')]
  public function preprocessInstructor(array &$variables): void {
    $this->attachPreviewLibrary($variables);
  }

  /**
   * Attaches the course card preview CSS.
   */
  protected function attachPreviewLibrary(array &$variables): void {
    $variables['#attached']['library'][] = 'csp_helper/course_card_preview';
  }

}
