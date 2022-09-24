<?php
use Acquia\Blt\Robo\Common\EnvironmentDetector;

// Do not submit to google or bing if on any environment other than prod.
if (!EnvironmentDetector::isAhProdEnv()) {
  $config['xmlsitemap_engines.settings']['engines'] = FALSE;
  $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
}
