<?php
// auth/email_start.php
require_once __DIR__ . '/../api/helpers.php';
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/mailer.php';
start_session_safe();
require_csrf();

$pdo = dbx();

// Entrada
$email = strtolower(trim($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_out(['ok' => false, 'error' => 'Correo inválido'], 422);
}

// Provider (solo informativo): por dominio
$provider = 'email';
if (preg_match('/@gmail\.com$/i', $email)) $provider = 'google';
elseif (preg_match('/@(outlook|hotmail|live)\.com$/i', $email)) $provider = 'microsoft';

// Código 6 dígitos
$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Limpia previos no usados
$pdo->prepare("UPDATE login_tokens SET used = 1 WHERE email = ? AND used = 0")->execute([$email]);

// Guarda token
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$stmt = $pdo->prepare("
  INSERT INTO login_tokens (email, ip_address, code, provider, created_at, expires_at, used)
  VALUES (:email, :ip, :code, :provider, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)
");
$stmt->execute([
  ':email' => $email,
  ':ip' => $ip,
  ':code' => $code,
  ':provider' => $provider,
]);

// Email
$subject = 'Tu código de acceso - AlertARD';
$html = '<p>Tu código de verificación es:</p>'
      . '<p style="font-size:22px;letter-spacing:4px;"><strong>' . htmlspecialchars($code) . '</strong></p>'
      . '<p>El código expira en 10 minutos.</p>';
$sent = send_mail_smart($email, $subject, $html);

// En localhost devuelve también el code para pruebas
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (strpos($host, 'localhost') !== false) || ($host === '127.0.0.1');

json_out(['ok' => true, 'sent' => $sent]);
