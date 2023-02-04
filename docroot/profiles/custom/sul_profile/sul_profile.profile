<?php

/**
 * @file
 * sul_profile.profile
 */

/**
 * Implements hook_install_tasks().
 */
function sul_profile_install_tasks(&$install_state) {
  return ['sul_profile_final_task' => []];
}

/**
 * Perform final tasks after the profile has completed installing.
 *
 * @param array $install_state
 *   Current install state.
 */
function sul_profile_final_task(array &$install_state) {
  \Drupal::service('plugin.manager.install_tasks')->runTasks($install_state);
}

function mikes(){
  $vocabs = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
  $template = \Drupal::config('jsonapi_extras.jsonapi_resource_config.taxonomy_term--basic_page_types')->get('resourceFields');
  /** @var \Drupal\taxonomy\VocabularyInterface $vocab */
  foreach($vocabs as $vocab){
    if($vocab->id() == 'basic_page_types'){
      continue;
    }

    $config = \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig::create([
      'id' => 'taxonomy_term--' . $vocab->id(),
      'path' => 'taxonomy_term/'. $vocab->id(),
      'disabled' => false,
      'resourceType'=>'taxonomy_term--' . $vocab->id(),
    ]);
    $config->set('resourceFields', $template);
    $config->save();
  }
}
