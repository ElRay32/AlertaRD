<?php
require __DIR__.'/oauth_common.php';
$c = cfg()['microsoft'];

if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state_ms'] ?? '')) {
  die('Estado inválido.');
}
unset($_SESSION['oauth_state_ms']);

if (!isset($_GET['code'])) {
  die('Sin código de autorización.');
}
$code = $_GET['code'];

$token_url = 'https://login.microsoftonline.com/'.$c['tenant'].'/oauth2/v2.0/token';
$post = [
  'client_id' => $c['client_id'],
  'client_secret' => $c['client_secret'],
  'code' => $code,
  'redirect_uri' => $c['redirect_uri'],
  'grant_type' => 'authorization_code',
];

$ch = curl_init($token_url);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query($post),
  CURLOPT_RETURNTRANSFER => true,
]);
$res = curl_exec($ch);
if ($res === false) die('Error de red.');
$tok = json_decode($res, true);
curl_close($ch);

$id_token = $tok['id_token'] ?? null;
if (!$id_token) die('Sin id_token.');

$claims = decode_jwt_noverify($id_token);

// En Microsoft, el email puede venir como 'preferred_username' o 'email'
$email = $claims['email'] ?? $claims['preferred_username'] ?? null;
$name  = $claims['name'] ?? null;
$sub   = $claims['sub'] ?? null;

if (!$email) die('No se obtuvo email.');

$allowed = $c['allowed_domains'];
if ($allowed && !in_array(email_domain($email), $allowed, true)) {
  die('Dominio no permitido para reporteros.');
}

$user_id = upsert_reporter($email, $name, 'microsoft', $sub, null);

$_SESSION['user_id'] = $user_id;
$_SESSION['name']    = $name ?: $email;
$_SESSION['role']    = 'reporter';

header('Location: ' . base_url() . 'index.php');
exit;
