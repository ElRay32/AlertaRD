<?php
require __DIR__.'/oauth_common.php';
$c = cfg()['google'];

$state = random_state();
$_SESSION['oauth_state_google'] = $state;

$params = [
  'client_id' => $c['client_id'],
  'redirect_uri' => $c['redirect_uri'],
  'response_type' => 'code',
  'scope' => implode(' ', $c['scopes']),
  'include_granted_scopes' => 'true',
  'access_type' => 'offline',
  'state' => $state,
  'prompt' => 'select_account',
];
$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: '.$auth_url);
exit;
