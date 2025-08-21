<?php
require __DIR__.'/email_lib.php';

if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: /alertard/auth/login.php'); exit; }

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die('Email inválido.');
}
if (!is_allowed_domain($email)) {
  die('Dominio no permitido. Usa un correo de Gmail/Outlook/Hotmail/Live.');
}

$pdo = dbx();

// Rate limit simple: 1 minuto entre envíos
$cfg = ecfg();
$recent = $pdo->prepare("SELECT created_at FROM login_tokens WHERE email=? ORDER BY id DESC LIMIT 1");
$recent->execute([$email]);
$last = $recent->fetchColumn();
if ($last && (time() - strtotime($last) < $cfg['resend_wait_minutes']*60)) {
  // Sigue adelante pero no spammees el correo: reusa último código si aún no expiró
  $sel = $pdo->prepare("SELECT code, expires_at FROM login_tokens WHERE email=? AND used=0 ORDER BY id DESC LIMIT 1");
  $sel->execute([$email]);
  $r = $sel->fetch();
  if ($r && strtotime($r['expires_at']) > time()) {
    $code = $r['code'];
  }
}

if (empty($code)) {
  $code = gen_code();
  $ins = $pdo->prepare("INSERT INTO login_tokens(email, code, provider, created_at, expires_at, used)
                        VALUES (?,?,?,?,?,0)");
  $ins->execute([$email, $code, provider_from_email($email), now_mysql(), plus_minutes($cfg['code_ttl_minutes'])]);
}

// Enviar email (si está configurado; si no, modo debug mostrará el código)
@send_code_email($email, $code);

// Guarda email en sesión para el paso siguiente
$_SESSION['otp_email'] = $email;
$_SESSION['otp_last_code'] = $code; // sólo se mostrará si debug_show_code=true

header('Location: /alertard/auth/email_verify.php');
exit;
