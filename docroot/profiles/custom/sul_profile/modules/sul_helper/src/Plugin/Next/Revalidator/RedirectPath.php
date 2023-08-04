<?php

namespace Drupal\sul_helper\Plugin\Next\Revalidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Plugin\ConfigurableRevalidatorBase;
use Drupal\next\Plugin\RevalidatorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a revalidator for paths.
 *
 * @Revalidator(
 *  id = "redirect_path",
 *  label = "Redirect Path",
 *  description = "Path-based on-demand revalidation."
 * )
 */
class RedirectPath extends ConfigurableRevalidatorBase implements RevalidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function revalidate(EntityActionEvent $event): bool {
    $revalidated = FALSE;
    $sites = $event->getSites();
    if (!count($sites)) {
      return FALSE;
    }
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() != 'redirect') {
      return FALSE;
    }
    $paths = ['/' . $entity->getSource()['path']];

    foreach ($paths as $path) {
      foreach ($sites as $site) {
        try {
          $revalidate_url = $site->getRevalidateUrlForPath($path);

          if (!$revalidate_url) {
            throw new \Exception('No revalidate url set.');
          }

          if ($this->nextSettingsManager->isDebug()) {
            $this->logger->notice('(@action): Revalidating path %path for the site %site. URL: %url', [
              '@action' => $event->getAction(),
              '%path' => $path,
              '%site' => $site->label(),
              '%url' => $revalidate_url->toString(),
            ]);
          }

          $response = $this->httpClient->request('GET', $revalidate_url->toString());
          if ($response && $response->getStatusCode() === Response::HTTP_OK) {
            if ($this->nextSettingsManager->isDebug()) {
              $this->logger->notice('(@action): Successfully revalidated path %path for the site %site. URL: %url', [
                '@action' => $event->getAction(),
                '%path' => $path,
                '%site' => $site->label(),
                '%url' => $revalidate_url->toString(),
              ]);
            }

            $revalidated = TRUE;
          }
        }
        catch (\Exception $exception) {
          watchdog_exception('next', $exception);
          $revalidated = FALSE;
        }
      }
    }

    return $revalidated;
  }

}
