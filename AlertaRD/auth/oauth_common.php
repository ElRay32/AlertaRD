<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function cfg() {
  static $cfg = null;
  if ($cfg === null) $cfg = require __DIR__.'/oauth_config.php';
  return $cfg;
}
function db() {
  require __DIR__.'/../api/db.php'; // te deja $pdo
  return $pdo;
}
function random_state($len=32) {
  return bin2hex(random_bytes($len/2));
}
function base_url() {
  return rtrim(cfg()['base_url'], '/') . '/';
}
function now_mysql() { return date('Y-m-d H:i:s'); }

// JWT decode **sin** verificación de firma (DEV). Para producción: validar firma (JWKs).
function decode_jwt_noverify($jwt) {
  $parts = explode('.', $jwt);
  if (count($parts) < 2) return null;
  $payload = $parts[1];
  $payload .= str_repeat('=', 3 - (3 + strlen($payload)) % 4); // pad
  $json = base64_decode(strtr($payload, '-_', '+/'));
  return json_decode($json, true);
}

function email_domain($email) {
  $pos = strpos($email, '@');
  return $pos!==false ? substr($email, $pos+1) : '';
}

// Crea/actualiza reportero por email; devuelve user_id
function upsert_reporter($email, $name, $provider, $sub, $picture=null) {
  $pdo = db();
  // busca por email
  $sel = $pdo->prepare("SELECT id, role_id FROM users WHERE email=?");
  $sel->execute([$email]);
  $u = $sel->fetch();
  if ($u) {
    $upd = $pdo->prepare("UPDATE users SET name=COALESCE(?,name), provider=?, provider_sub=?, picture_url=?, last_login_at=? WHERE id=?");
    $upd->execute([$name ?: null, $provider, $sub, $picture, now_mysql(), $u['id']]);
    return $u['id'];
  } else {
    // role_id=1 -> reportero
    $ins = $pdo->prepare("INSERT INTO users(name,email,role_id,provider,provider_sub,picture_url,last_login_at) VALUES (?,?,?,?,?,?,?)");
    $ins->execute([$name ?: $email, $email, 1, $provider, $sub, $picture, now_mysql()]);
    return $pdo->lastInsertId();
  }
}
