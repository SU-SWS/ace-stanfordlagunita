<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * SuPress form and field widget hooks.
 */
class PressFormHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_options_select_form_alter')]
  public function optionsSelectWidgetAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    if ($context['items']->getName() == 'sup_search_subject') {
      // Only show the top level terms and the "None" option.
      $field_widget_complete_form['widget']['#options'] = array_filter($field_widget_complete_form['widget']['#options'], fn($option) => !str_starts_with((string) $option, '-') || $option == '- None -');
    }
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_single_element_viewfield_select_form_alter')]
  public function viewfieldSelectWidgetAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $element['view_options']['helper'] = [
      '#type' => 'container',
      'help_text' => [
        '#markup' => $this->t('Book view arguments should be constructed like %subject/%series/%tags/%imprints/%work. To skip an argument, provide an empty quotes: "".', [
          '%subject' => 'subject-term-names',
          '%series' => 'series-term-names',
          '%tags' => 'book-tag-names',
          '%imprints' => 'book-imprint-names',
          '%work' => 'work+id+numbers',
        ]),
      ],
      '#weight' => 21,
      '#states' => [
        'visible' => [
          '[name="su_list_view[0][display_id]"]' => [
            ['value' => 'award_winners'],
            ['value' => 'book_list'],
          ],
        ],
      ],
    ];

    $element['view_options']['seasonal_helper'] = [
      '#type' => 'container',
      'help_text' => [
        '#markup' => $this->t('The argument should be the exact string of the "Catalog Season" field'),
      ],
      '#weight' => 21,
      '#states' => [
        'visible' => [
          '[name="su_list_view[0][display_id]"]' => ['value' => 'seasonal_list'],
        ],
      ],
    ];
  }

}
