<?php
// sul_helper/src/Layouts/LayoutWithHeading.php

namespace Drupal\sul_helper\Layouts;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trait for adding heading functionality to layouts.
 */
trait LayoutWithHeading {
  
  /**
   * Add heading element to the form.
   */
  protected function addHeadingElement(array &$form, FormStateInterface $form_state) {
    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section Heading'),
      '#description' => $this->t('Optional heading for this section.'),
      '#default_value' => $this->configuration['heading'] ?? '',
      '#maxlength' => 255,
      '#weight' => -99,
    ];
    
    $form['heading_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading Level'),
      '#default_value' => $this->configuration['heading_level'] ?? 'h2',
      '#options' => [
        'h2' => $this->t('H2'),
        'h3' => $this->t('H3'),
        'h4' => $this->t('H4'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="heading"]' => ['filled' => TRUE],
        ],
      ],
      '#weight' => -98,
    ];

    $form['display_heading_gradient'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Heading Gradient Accent'),
      '#description' => $this->t('Add a gradient accent line to the right of list heading.'),
      '#default_value' => $this->configuration['display_heading_gradient'] ?? FALSE,
      '#weight' => -97,
    ];
    
    return $form;
  }
  
  /**
   * Submit handler for heading form element.
   */
  protected function submitHeadingForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['heading'] = $form_state->getValue('heading');
    $this->configuration['heading_level'] = $form_state->getValue('heading_level');
  }
}