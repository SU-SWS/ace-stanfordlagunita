<?php

/**
 * @file
 * summer_helper.deploy.php
 */

/**
 * Move global message config page to new content entity.
 */
function summer_helper_deploy_global_message() {
  /** @var \Drupal\next\Entity\NextEntityTypeConfigInterface $next_entity */
  $next_entity = \Drupal::entityTypeManager()
    ->getStorage('next_entity_type_config')
    ->create([
      'id' => 'summer_entity.global_msg',
    ]);
  $next_entity->setSiteResolver('site_selector')
    ->setSiteResolverConfiguration('site_selector', ['sites' => ['vercel' => 'vercel']])
    ->setRevalidator('path')
    ->setRevalidatorConfiguration('path', ['additional_paths' => '/tags/global-message'])
    ->save();

  /** @var \Drupal\config_pages\ConfigPagesInterface[] $global_messages */
  $global_messages = \Drupal::entityTypeManager()
    ->getStorage('config_pages')
    ->loadByProperties(['type' => 'stanford_global_message']);
  if ($global_messages) {
    $global_message = reset($global_messages);
    \Drupal::entityTypeManager()->getStorage('summer_entity')->create([
      'bundle' => 'global_msg',
      'label' => $global_message->get('su_global_msg_header')->getString(),
      'sum_global_msg_body' => $global_message->get('su_global_msg_message')
        ->getValue(),
      'sum_global_msg_link' => $global_message->get('su_global_msg_link')
        ->getValue(),
    ])->save();
  }
}
