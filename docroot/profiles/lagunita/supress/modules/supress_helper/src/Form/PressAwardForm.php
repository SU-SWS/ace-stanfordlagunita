<?php

declare(strict_types=1);

namespace Drupal\supress_helper\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the press award entity edit forms.
 */
final class PressAwardForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The award %label has been updated.', $message_args));
        $this->logger('supress_helper')->notice('The press award %label has been updated.', $logger_args);
        break;
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
