<?php

/**
 * @file
 * Post update functions for supress_helper.
 */

/**
 * Set the default weight on book ancillary nodes based on their title.
 */
function supress_helper_post_update_ancillary_weights(array &$sandbox): string {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['ids'])) {
    // Only nodes without a weight are affected by the presave hook.
    $sandbox['ids'] = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'sup_book_ancillary')
      ->notExists('sup_ancillary_weight')
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
  }

  $ids = array_splice($sandbox['ids'], 0, 50);
  /** @var \Drupal\node\NodeInterface $node */
  foreach ($node_storage->loadMultiple($ids) as $node) {
    // Saving triggers PressHooks::nodePresave(), which sets the weight.
    $node->save();
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : ($sandbox['total'] - count($sandbox['ids'])) / $sandbox['total'];
  return sprintf('Processed %d of %d ancillary nodes.', $sandbox['total'] - count($sandbox['ids']), $sandbox['total']);
}
