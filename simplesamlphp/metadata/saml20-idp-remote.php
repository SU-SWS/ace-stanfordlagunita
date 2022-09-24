<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

$metadata['https://idp.stanford.edu/'] = [
  'name' => [
    'en' => 'Stanford University WebLogin',
  ],
  'description' => 'Stanford University WebLogin',
  'SingleSignOnService' => 'https://login.stanford.edu/idp/profile/SAML2/Redirect/SSO',
  'certFingerprint' => '2B:41:A2:66:6A:4E:3F:40:C6:30:55:6A:1F:EC:C3:E3:0B:CE:EE:8F',
];

$metadata['https://idp-uat.stanford.edu/'] = [
  'name' => [
    'en' => 'Stanford University WebLogin',
  ],
  'description' => 'Stanford University WebLogin',
  'SingleSignOnService' => 'https://login-uat.stanford.edu/idp/profile/SAML2/Redirect/SSO',
  'certFingerprint' => '6E:C8:18:F6:F9:3D:00:9D:8D:AB:18:02:FD:1A:41:14:ED:98:E4:31',
];
