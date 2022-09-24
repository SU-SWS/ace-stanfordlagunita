<?php
use Acquia\Blt\Robo\Common\EnvironmentDetector;

// Do not provide an account for GA on non-prod.
if (!EnvironmentDetector::isAhProdEnv()) {
  $config['google_analytics.settings']['account'] = '';
}
