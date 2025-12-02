<?php

declare(strict_types=1);

namespace Drupal\summer_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Summer hooks.
 */
class SummerHooks {

  /**
   * Implements hook_viewfield_argument_suggestion_vocabs_alter().)
   */
  #[Hook('viewfield_argument_suggestion_vocabs_alter')]
  public function viewfieldArgVocabs(array &$vocabs, array $view) {
    if ($view['view'] == 'sum_courses') {
      $vocabs[] = 'sum_course_learner';
    }
  }

}
