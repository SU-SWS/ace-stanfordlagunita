<?php

namespace Drupal\sul_helper\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

class SulCommands extends DrushCommands {

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * Set the secret string on the consumer entity.
   *
   * @param string $uuid
   *   Consumer entity uuid.
   * @param string $secret
   *   Consumer secret string.
   *
   * @command sul-profile:set-consumer-secret
   */
  public function setConsumerSecret(string $uuid, string $secret) {
    $consumer_storage = $this->entityTypeManager->getstorage('consumer');
    $consumer = $consumer_storage->loadByProperties(['client_id' => $uuid]);
    if (empty($consumer)) {
      $consumer_storage->create([
        'uuid' => $uuid,
        'label' => 'NextJS Consumer',
        'description' => '',
        'is_default' => TRUE,
        'secret' => $secret,
      ]);
      return;
    }
    /** @var \Drupal\consumers\Entity\Consumer */
    $consumer = reset($consumer);
    $consumer->set('secret', $secret);
    $consumer->save();
  }

  /**
   * Create a NextJS site entity.
   *
   * @param string $label
   *   Label of the site entity.
   * @param string $base_url
   *   URL for the nextjs site.
   * @param string|null $preview_url
   *   Preview URL.
   * @param string|null $preview_secret
   *   Preview Secret.
   *
   * @command sul-profile:create-nextjs-site
   */
  public function createNextJsSite(string $label, string $base_url, string $preview_url = NULL, string $preview_secret = NULL) {
    $next_site_id = preg_replace('/[^a-z\d]/', '_', strtolower($label));
    $storage = $this->entityTypeManager->getStorage('next_site');
    /** @var \Drupal\next\Entity\NextSiteInterface $entity */
    $entity = $storage->load($next_site_id);
    if (!$entity) {
      $entity = $storage->create([
        'id' => $next_site_id,
        'label' => $label,
      ]);
    }

    $entity->set('base_url', $base_url);
    $entity->set('preview_url', $preview_url);
    $entity->set('preview_secret', $preview_secret);
    $entity->save();
    $this->connectEntityTypes($next_site_id);
  }

  /**
   * Connect entity types to the NextJS site ID.
   */
  protected function connectEntityTypes($next_site_id) {
    $node_types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();
    $next_storage = $this->entityTypeManager->getStorage('next_entity_type_config');
    foreach (array_keys($node_types) as $node_type) {
      /** @var \Drupal\next\Entity\NextEntityTypeConfigInterface $entity */
      $entity = $next_storage->load("node.$node_type");
      if (!$entity) {
        $entity = $next_storage->create(['id' => "node.$node_type"]);
      }
      $entity->setSiteResolver('site_selector')
        ->setConfiguration(['sites' => [$next_site_id => $next_site_id]]);

      /** @var \Drupal\next\Plugin\ConfigurableSiteResolverInterface $site_resolver */
      $site_resolver = $entity->getSiteResolver();
      $site_resolver->setConfiguration(['sites' => [$next_site_id => $next_site_id]]);

      $entity->save();
    }
  }

}
