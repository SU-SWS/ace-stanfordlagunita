<?php

/**
 * @file
 * sul_helper.post_update.php
 */

/**
 * Swap original event views with the custom sul event view.
 */
function sul_helper_post_update_event_views(&$sandbox) {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  if (empty($sandbox['ids'])) {
    $query = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'stanford_lists');

    $condition_group = $query->orConditionGroup();
    $condition_group->condition('su_list_view.target_id', 'stanford_events')
      ->condition('su_list_view.target_id', 'sul_shared_tag_events');
    $sandbox['ids'] = $query->condition($condition_group)->execute();
    $sandbox['total'] = count($sandbox['ids']);
  }

  $node_ids = array_splice($sandbox['ids'], 0, 10);

  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  foreach ($paragraph_storage->loadMultiple($node_ids) as $paragraph) {
    $list_value = $paragraph->get('su_list_view')->getValue();
    foreach ($list_value as &$value) {
      if ($value['target_id'] == 'sul_shared_tag_events') {
        $value['target_id'] = 'sul_events';
        $value['display_id'] = 'shared_tags_cards';
      }

      $value['target_id'] = str_replace('stanford_events', 'sul_events', $value['target_id']);
    }
    $paragraph->set('su_list_view', $list_value)->save();
  }

  $sandbox['#finished'] = count($sandbox['ids']) ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;
}

/**
 * Swap original event views with the custom sul event view.
 */
function sul_helper_post_update_news_views(&$sandbox) {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  if (empty($sandbox['ids'])) {
    $query = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'stanford_lists')
      ->condition('su_list_view.target_id', 'stanford_news');
    $sandbox['ids'] = $query->execute();
    $sandbox['total'] = count($sandbox['ids']);
  }

  $node_ids = array_splice($sandbox['ids'], 0, 10);

  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  foreach ($paragraph_storage->loadMultiple($node_ids) as $paragraph) {
    $list_value = $paragraph->get('su_list_view')->getValue();
    foreach ($list_value as &$value) {
      $value['target_id'] = str_replace('stanford_news', 'sul_news', $value['target_id']);
    }
    $paragraph->set('su_list_view', $list_value)->save();
  }

  $sandbox['#finished'] = count($sandbox['ids']) ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;
}
