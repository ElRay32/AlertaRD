<?php
require __DIR__.'/oauth_common.php';
$c = cfg()['microsoft'];

$state = random_state();
$_SESSION['oauth_state_ms'] = $state;

$params = [
  'client_id' => $c['client_id'],
  'response_type' => 'code',
  'redirect_uri' => $c['redirect_uri'],
  'response_mode' => 'query',
  'scope' => implode(' ', $c['scopes']), // openid email profile offline_access
  'state' => $state,
];
$auth_url = 'https://login.microsoftonline.com/'.$c['tenant'].'/oauth2/v2.0/authorize?' . http_build_query($params);
header('Location: '.$auth_url);
exit;
