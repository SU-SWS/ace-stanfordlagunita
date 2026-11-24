<?php

/**
 * @file
 * The port each site is served on in the dev container.
 *
 * Read by post-start.sh (Apache vhosts), on-create.sh (databases) and
 * sites.php (host to site directory). Keep the ports in sync with
 * forwardPorts in devcontainer.json.
 */

return [
  'csp' => 8001,
  'library' => 8002,
  'summer' => 8003,
  'supress' => 8004,
];
