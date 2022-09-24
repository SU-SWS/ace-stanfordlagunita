<?php

/**
 * @file
 * Include any necessary changes to the authsources config here.
 */

// This file should be copied into vendor/simplesamlphp/simplesamlphp/config.
$root = dirname(__FILE__, 5);

// Store saml certs outside of the repo directory.
$config['default-sp']['privatekey'] = "$root/keys/saml.pem";
$config['default-sp']['certificate'] = "$root/keys/saml.crt";
