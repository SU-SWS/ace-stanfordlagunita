<?php

declare(strict_types=1);

namespace Drupal\LagunitaDrush\Drush\Commands;

use Drupal\Core\Serialization\Yaml;
use Drupal\SwsDrush\Drush\Commands\SwsCommandsTrait;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Drush\Exceptions\CommandFailedException;

class LagunitaCommands extends DrushCommands {

  use SwsCommandsTrait;

  #[CLI\Command(name: 'lagunita:new-profile')]
  #[CLI\Argument(name: 'profile_name', description: 'Machine name of the new profile')]
  #[CLI\Argument(name: 'site_name', description: 'Machine name of the new site')]
  #[CLI\Argument(name: 'config_prefix', description: 'Short prefix that will be used for config split settings. Up to 4 characters.')]
  public function newProfile(string $profile_name, string $site_name, string $config_prefix) {
    if (preg_match('/[^a-z0-9_]/', $site_name)) {
      throw new CommandFailedException('Invalid site name. Only lowercase alpha numeric characters are allowed.');
    }
    if (preg_match('/[^a-z0-9_]/', $profile_name)) {
      throw new CommandFailedException('Invalid profile name. Only lowercase alpha numeric characters are allowed.');
    }
    if (preg_match('/[^a-z]/', $config_prefix) || strlen($config_prefix) > 4) {
      throw new CommandFailedException('Invalid config prefix. Only lowercase characters and up to 4 characters.');
    }
    //    $this->localMachineHelper()
    //      ->executeFromCmd("git subtree add --squash --prefix=docroot/profiles/lagunita/$profile_name git@github.com:SU-SWS/stanford_profile.git 12.x");
    $file_system = $this->localMachineHelper()->getFilesystem();
    $composer = json_decode($file_system->readFile($this->getDir() . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $script = $composer['scripts']['pull-sul'];
    $script = preg_replace('/sul_profile/', $profile_name, $script);
    $composer['scripts']['pull-' . str_replace('_', '-', $site_name)] = $script;
    $composer['extra']['merge-plugin']['require'][] = "docroot/profiles/lagunita/$profile_name/composer.json";
    asort($composer['extra']['merge-plugin']['require']);
    $composer['extra']['merge-plugin']['require'] = array_values(array_unique($composer['extra']['merge-plugin']['require']));

    $file_system->dumpFile($this->getDir() . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $drush = Yaml::decode($file_system->readFile($this->getDir() . '/drush/drush.yml'));
    $drush['command']['sws']['options']['multisites'][] = $site_name;
    asort($drush['command']['sws']['options']['multisites']);
    $drush['command']['sws']['options']['multisites'] = array_values(array_unique($drush['command']['sws']['options']['multisites']));
    $file_system->dumpFile($this->getDir() . '/drush/drush.yml', Yaml::encode($drush));
    $this->updateProfile($profile_name, $site_name, $config_prefix);
    $this->localMachineHelper()->execute(['drush', 'settings']);

    $site_settings = <<<PHP
<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

\$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/lagunita/$profile_name/config/sync';

\$next_domain = FALSE;
if (EnvironmentDetector::isDevEnv()) {
  \$next_domain = 'https://$site_name-dev.vercel.app';
}
elseif (EnvironmentDetector::isStageEnv()) {
  \$next_domain = 'https://$site_name-test.vercel.app';
}
elseif (EnvironmentDetector::isLocalEnv()) {
  \$next_domain = 'http://localhost:3000';
}

if (\$next_domain) {
  \$config['next.next_site.vercel'] = [
    'base_url' => \$next_domain,
    'preview_url' => "\$next_domain/api/draft",
    'revalidate_url' => "\$next_domain/api/revalidate",
  ];
}

PHP;

    $file_system->dumpFile($this->getDir() . "/docroot/sites/$site_name/settings/includes.settings.php", $site_settings);
    $alias = ['local' => ['uri' => $site_name, 'root' => '${env.cwd}/docroot']];
    $file_system->dumpFile($this->getDir() . "/drush/sites/$site_name.site.yml", Yaml::encode($alias));
    $site_config = [
      'site' => [
        'profile' => $profile_name,
        'remote_alias' => "$site_name.prod",
      ],
    ];
    $file_system->dumpFile($this->getDir() . "/docroot/sites/$site_name/sws.yml", Yaml::encode($site_config));
  }

  /**
   * Rename files and replace file contents to from stanford_profile -> name.
   *
   * @param string $profile_name
   *   New profile name.
   * @param string $site_name
   *   New site name.
   * @param string $config_prefix
   *   Prefix for configs in the config split settings.
   */
  protected function updateProfile(string $profile_name, string $site_name, string $config_prefix) {
    $file_system = $this->localMachineHelper()->getFilesystem();
    $profile_path = $this->getDir() . "/docroot/profiles/lagunita/$profile_name";
    $file_system->remove("$profile_path/composer.json");
    $site_name_readable = ucwords(str_replace('_', ' ', $site_name));
    $this->localMachineHelper()->execute([
      'composer',
      'init',
      "--name=su-sws/$profile_name",
      "--description='Installation profile for $site_name_readable'",
      '--type=drupal-custom-profile',
      '--stability=dev',
      '-n',
    ], NULL, $profile_path);

    foreach (scandir($profile_path) as $file) {
      if (str_starts_with($file, 'stanford_profile')) {
        $new_file_name = str_replace('stanford_profile', $profile_name, $file);
        $file_system->copy("$profile_path/$file", "$profile_path/$new_file_name");
        $file_system->remove("$profile_path/$file");

        $this->localMachineHelper()
          ->executeFromCmd("sed -i 's/Stanford Profile/$site_name_readable Profile/g' $new_file_name", NULL, $profile_path);
        $this->localMachineHelper()
          ->executeFromCmd("sed -i 's/stanford_profile/$profile_name/g' $new_file_name", NULL, $profile_path);
      }
    }

    $from_to = [
      'stanford_profile_' => 'replace_me_back',
      'stanford_profile' => $profile_name,
      "test_$profile_name" => 'test_stanford_profile',
      'replace_me_back' => 'stanford_profile_',
      "{$profile_name}_admin" => 'stanford_profile_admin',
      "{$profile_name}_styles" => 'stanford_profile_styles',
      "{$profile_name}_helper" => 'stanford_profile_styles',
    ];
    foreach ($from_to as $from => $to) {
      $this->localMachineHelper()
        ->executeFromCmd("find . -type f -print0 | xargs -0 sed -i 's/$from/$to/g'", NULL, $profile_path);
    }
    $helper_info = [
      'name' => "$profile_name Helper",
      'type' => 'module',
      'description' => "Provides additional functionality for $profile_name Profile",
      'package' => 'Stanford',
      'core_version_requirement' => '^11.1',
    ];
    $file_system->dumpFile("$profile_path/{$profile_name}_helper/{$profile_name}_helper.info.yml", Yaml::encode($helper_info));
    $core_extensions = Yaml::decode($file_system->readFile("$profile_path/config/sync/core.extension.yml"));
    $core_extensions['module']["{$profile_name}_helper"] = 20;
    asort($core_extensions['module']);

    $file_system->dumpFile("$profile_path/config/sync/core.extension.yml", Yaml::encode($core_extensions));

    $config_split = [
      'uuid' => $this->getUuid(),
      'langcode' => 'en',
      'dependencies' => [],
      'id' => $site_name,
      'label' => $site_name_readable,
      'description' => "Configuration for $site_name_readable",
      'weight' => 0,
      'stackable' => FALSE,
      'no_patching' => FALSE,
      'storage' => 'collection',
      'folder' => "profiles/lagunita/$profile_name/config/$site_name",
      'module' => [],
      'theme' => [],
      'complete_list' => [
        "field.field.node.{$config_prefix}_*",
        "field.field.media.{$config_prefix}_*",
        "field.field.paragraph.{$config_prefix}_*",
        "field.field.config_pages.{$config_prefix}_*",
        "field.field.taxonomy_term.{$config_prefix}_*",
        "field.storage.node.{$config_prefix}_*",
        "field.storage.media.{$config_prefix}_*",
        "field.storage.paragraph.{$config_prefix}_*",
        "field.storage.config_pages.{$config_prefix}_*",
        "field.storage.taxonomy_term.{$config_prefix}_*",
        "node.type.{$config_prefix}_*",
        "paragraphs.paragraphs_type.{$config_prefix}_*",
        "taxonomy.vocabulary.{$config_prefix}_*",
        "user.role.{$config_prefix}_*",
        "views.view.{$config_prefix}_*",
        "migrate_plus.migration_group.{$config_prefix}_*",
        "migrate_plus.migration.{$config_prefix}_*",
        "pathauto.pattern.{$config_prefix}_*",
        "media.type.{$config_prefix}_*",
        "metatag.metatag_defaults.node__{$config_prefix}_*",
      ],
      'partial_list' => [
        '*su_*',
        '*stanford_*',
        'user.role.*',
      ],
    ];
    $file_system->dumpFile("$profile_path/config/sync/config_split.config_split.$site_name.yml", Yaml::encode($config_split));

    $this->localMachineHelper()->executeFromCmd("sed -i 's/field_prefix: su_/field_prefix: {$config_prefix}_/g' field_ui.settings.yml", null, "$profile_path/config/sync/");
  }

  protected function getUuid() {
    // Obtain a random string of 32 hex characters.
    $hex = bin2hex(random_bytes(16));

    // The variable names $time_low, $time_mid, $time_hi_and_version,
    // $clock_seq_hi_and_reserved, $clock_seq_low, and $node correlate to
    // the fields defined in RFC 4122 section 4.1.2.
    //
    // Use characters 0-11 to generate 32-bit $time_low and 16-bit $time_mid.
    $time_low = substr($hex, 0, 8);
    $time_mid = substr($hex, 8, 4);

    // Use characters 12-15 to generate 16-bit $time_hi_and_version.
    // The 4 most significant bits are the version number (0100 == 0x4).
    // We simply skip character 12 from $hex, and concatenate the strings.
    $time_hi_and_version = '4' . substr($hex, 13, 3);

    // Use characters 16-17 to generate 8-bit $clock_seq_hi_and_reserved.
    // The 2 most significant bits are set to one and zero respectively.
    $clock_seq_hi_and_reserved = base_convert(substr($hex, 16, 2), 16, 10);
    $clock_seq_hi_and_reserved &= 0b00111111;
    $clock_seq_hi_and_reserved |= 0b10000000;

    // Use characters 18-19 to generate 8-bit $clock_seq_low.
    $clock_seq_low = substr($hex, 18, 2);
    // Use characters 20-31 to generate 48-bit $node.
    $node = substr($hex, 20);

    // Re-combine as a UUID. $clock_seq_hi_and_reserved is still an integer.
    $uuid = sprintf('%s-%s-%s-%02x%s-%s',
      $time_low, $time_mid, $time_hi_and_version,
      $clock_seq_hi_and_reserved, $clock_seq_low,
      $node
    );

    return $uuid;
  }

}
