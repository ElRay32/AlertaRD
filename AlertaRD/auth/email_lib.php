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

// ====== NOTIFICACIONES ======
function notify_mail($to, $subject, $html_body, $text_body=null){
  $cfg = ecfg();
  $headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: '.$cfg['from_name'].' <'.$cfg['from_email'].'>'
  ];
  // Texto alterno simple
  if ($text_body === null) { $text_body = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html_body)); }
  // Fallback: muchos servidores aceptan HTML con Content-type text/html
  return mail($to, $subject, $html_body, implode("\r\n", $headers));
}

function incidentById($id){
  $pdo = dbx();
  $sql = "SELECT i.*, u.email AS reporter_email, u.name AS reporter_name
          FROM incidents i
          LEFT JOIN users u ON u.id = i.reporter_user_id
          WHERE i.id = ?";
  $st = $pdo->prepare($sql); $st->execute([$id]);
  return $st->fetch();
}

function correctionById($id){
  $pdo = dbx();
  $sql = "SELECT c.*, u.email AS user_email, u.name AS user_name, i.title AS incident_title
          FROM incident_corrections c
          LEFT JOIN users u ON u.id = c.user_id
          LEFT JOIN incidents i ON i.id = c.incident_id
          WHERE c.id = ?";
  $st = $pdo->prepare($sql); $st->execute([$id]);
  return $st->fetch();
}

// Notifica al reportero cuando su incidente cambia de estado
function notify_incident_status($incident_id, $new_status, $note=null){
  $i = incidentById($incident_id);
  if (!$i || empty($i['reporter_email'])) return false;

  $title = $i['title'] ?: ('Incidente #'.$i['id']);
  if ($new_status === 'published') {
    $subject = "Tu reporte fue PUBLICADO: ".$title;
    $html = "<p>Hola {$i['reporter_name']},</p>
      <p>Tu reporte <strong>{$title}</strong> ha sido <strong>publicado</strong>.</p>
      <p>Puedes verlo aquí: <a href=\"".url_origin()."/alertard/incident.php?id={$i['id']}\">ver detalle</a></p>
      <p>Gracias por contribuir.</p>";
  } elseif ($new_status === 'rejected') {
    $subject = "Tu reporte fue RECHAZADO: ".$title;
    $extra = $note ? "<p><strong>Motivo:</strong> ".htmlspecialchars($note)."</p>" : "";
    $html = "<p>Hola {$i['reporter_name']},</p>
      <p>Lamentamos informarte que tu reporte <strong>{$title}</strong> fue <strong>rechazado</strong>.</p>
      {$extra}
      <p>Si crees que hubo un error, puedes volver a intentarlo con más información.</p>";
  } else {
    $subject = "Actualización de estado: ".$title;
    $html = "<p>Hola {$i['reporter_name']},</p><p>Tu reporte ha cambiado de estado a <strong>{$new_status}</strong>.</p>";
  }

  return notify_mail($i['reporter_email'], $subject, $html);
}

// Notifica al autor de una corrección cuando es aplicada/rechazada
function notify_correction_status($correction_id, $new_status, $note=null){
  $c = correctionById($correction_id);
  if (!$c || empty($c['user_email'])) return false;

  $title = $c['incident_title'] ?: ('Incidente #'.$c['incident_id']);
  if ($new_status === 'applied') {
    $subject = "Tu corrección fue APLICADA: ".$title;
    $html = "<p>Hola {$c['user_name']},</p>
      <p>Gracias. Tu corrección sobre <strong>{$title}</strong> fue <strong>aplicada</strong> por un validador.</p>";
  } elseif ($new_status === 'rejected') {
    $subject = "Tu corrección fue RECHAZADA: ".$title;
    $extra = $note ? "<p><strong>Motivo:</strong> ".htmlspecialchars($note)."</p>" : "";
    $html = "<p>Hola {$c['user_name']},</p>
      <p>La corrección que propusiste sobre <strong>{$title}</strong> fue <strong>rechazada</strong>.</p>{$extra}";
  } else {
    $subject = "Actualización de corrección: ".$title;
    $html = "<p>Hola {$c['user_name']},</p><p>Tu corrección ahora está en estado <strong>{$new_status}</strong>.</p>";
  }
  return notify_mail($c['user_email'], $subject, $html);
}

// Helper: base URL (simple) para armar links
function url_origin(){
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme.'://'.$host;
}

// ====== RATE LIMIT OTP ======
function otp_is_rate_limited($email, $ip){
  $pdo = dbx();
  // límites razonables (ajústalos si quieres)
  $maxPerHourIp = 25;     // pedidos de código por IP / hora
  $maxPerHourEmail = 10;  // pedidos de código por email / hora
  $maxPerDayEmail = 20;   // pedidos por email / día

  $q1 = $pdo->prepare("SELECT COUNT(*) FROM login_tokens WHERE ip_address=? AND created_at > NOW() - INTERVAL 1 HOUR");
  $q1->execute([$ip]); $ipH = (int)$q1->fetchColumn();

  $q2 = $pdo->prepare("SELECT COUNT(*) FROM login_tokens WHERE email=? AND created_at > NOW() - INTERVAL 1 HOUR");
  $q2->execute([$email]); $emH = (int)$q2->fetchColumn();

  $q3 = $pdo->prepare("SELECT COUNT(*) FROM login_tokens WHERE email=? AND created_at > NOW() - INTERVAL 1 DAY");
  $q3->execute([$email]); $emD = (int)$q3->fetchColumn();

  return ($ipH >= $maxPerHourIp) || ($emH >= $maxPerHourEmail) || ($emD >= $maxPerDayEmail);
}

function otp_register_attempt($id){
  $pdo = dbx();
  $pdo->prepare("UPDATE login_tokens SET verify_attempts=verify_attempts+1, last_attempt_at=NOW() WHERE id=?")->execute([$id]);
}

function otp_blocked($row){
  // bloquea si excede 5 intentos de verificación del código
  return ((int)$row['verify_attempts'] >= 5);
}
