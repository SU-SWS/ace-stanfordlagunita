<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for a press award entity type.
 *
 * @codeCoverageIgnore
 */
final class PressAwardSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sup_award_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['settings'] = [
      '#markup' => $this->t('No settings at this time for a press award entity type.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
      '#attributes' => ['disabled' => TRUE],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()
      ->addStatus($this->t('The configuration has been updated.'));
  }

}
