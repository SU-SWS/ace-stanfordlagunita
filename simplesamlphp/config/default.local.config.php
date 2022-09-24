<?php

$config['authproc.sp'] = [
  10 => [
    'class' => 'core:AttributeMap',
    'removeurnprefix',
    'oid2name',
  ],
  90 => 'core:LanguageAdaptor',
];


$config['store.type'] = 'sql';
$config['store.sql.prefix'] = 'simplesaml';
$config['store.sql.dsn'] = 'mysql:host=${drupal.db.host};dbname=${drupal.db.database}';
$config['store.sql.username'] = '${drupal.db.username}';
$config['store.sql.password'] = '${drupal.db.password}';

/**
 * The simplsamlphp/config/config.php file contains a default for
 * $config['baseurlpath'] which looks to see if there is a
 * $_SERVER['HTTP_X_FORWARDED_PROTO'] setting of `https`. If there is,
 * it sets the baseurlpath to port 443, and the protocol to https.
 * However, if that server variable isn't there, it defaults to http, which is usually
 * the case in your local environment.
 * You can override this for your local environment by un-commenting the lines
 * below, if you wish to force SSL in your local environment.
 */
 #if (isset($_SERVER['HTTP_HOST'])) {
 #  $config['baseurlpath'] = 'https://' . $_SERVER['HTTP_HOST'] . ':443/simplesaml/';
 #}

/**
 * If you are enforcing SSL connections locally, you may also set secure cookies by
 * changing these variable to TRUE.
 */
$config['session.cookie.secure'] = FALSE;
$config['language.cookie.secure'] = FALSE;
