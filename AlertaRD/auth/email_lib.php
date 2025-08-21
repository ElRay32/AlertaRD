<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function ecfg() {
  static $cfg=null; if ($cfg===null) $cfg = require __DIR__.'/email_config.php';
  return $cfg;
}
function dbx() {
  require __DIR__.'/../api/db.php'; // deja $pdo
  return $pdo;
}
function now_mysql(){ return date('Y-m-d H:i:s'); }
function plus_minutes($m){ return date('Y-m-d H:i:s', time()+($m*60)); }

function email_domain($email){
  $p = strpos($email,'@'); return $p!==false ? strtolower(substr($email,$p+1)) : '';
}
function is_allowed_domain($email){
  return in_array(email_domain($email), ecfg()['allowed_domains'], true);
}
function provider_from_email($email){
  $d = email_domain($email);
  if ($d==='gmail.com' || $d==='googlemail.com') return 'google';
  if (in_array($d, ['outlook.com','hotmail.com','live.com'], true)) return 'microsoft';
  return 'email';
}
function gen_code(){
  return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Muy simple: intenta SMTP si hay config, si no usa mail()
function send_code_email($to, $code){
  $cfg = ecfg();
  $subject = 'Tu código de acceso';
  $body = "Tu código de acceso es: $code\nVence en {$cfg['code_ttl_minutes']} minutos.\n\nAlertaRD";

  $smtp = $cfg['smtp'];
  if (!empty($smtp['host'])) {
    // Enviar por SMTP nativo con sockets (sin librerías externas)
    $headers = [
      'MIME-Version: 1.0',
      'Content-type: text/plain; charset=UTF-8',
      'From: '.$cfg['from_name'].' <'.$cfg['from_email'].'>'
    ];
    // Para simplificar, usamos mail() con -f (remitente). Si quieres SMTP real, puedes instalar PHPMailer.
    return mail($to, $subject, $body, implode("\r\n", $headers), '-f '.$cfg['from_email']);
  } else {
    $headers = [
      'MIME-Version: 1.0',
      'Content-type: text/plain; charset=UTF-8',
      'From: '.$cfg['from_name'].' <'.$cfg['from_email'].'>'
    ];
    return mail($to, $subject, $body, implode("\r\n", $headers));
  }
}

// Crea/actualiza reportero
function upsert_reporter_email($email){
  $pdo = dbx();
  $sel = $pdo->prepare("SELECT id FROM users WHERE email=?");
  $sel->execute([$email]);
  $id = $sel->fetchColumn();
  $name = ucwords(str_replace(['.','_'], ' ', strstr($email, '@', true)));

  if ($id) {
    $upd = $pdo->prepare("UPDATE users SET name=COALESCE(?,name), role_id=COALESCE(role_id,1) WHERE id=?");
    $upd->execute([$name ?: null, $id]);
    return $id;
  } else {
    $ins = $pdo->prepare("INSERT INTO users(name,email,role_id) VALUES (?,?,1)");
    $ins->execute([$name ?: $email, $email]);
    return $pdo->lastInsertId();
  }
}


