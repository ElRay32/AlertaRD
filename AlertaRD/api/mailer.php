<?php
// api/mailer.php
// Enviar correo vía PHPMailer (si está instalado) o función mail() como fallback.
require_once __DIR__ . '/helpers.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
  require_once $autoload;
}

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
  // Estructura típica: vendor/phpmailer/phpmailer/src/*.php
  $base = __DIR__ . '/../vendor/phpmailer/phpmailer/src';
  if (file_exists($base . '/PHPMailer.php')) {
    require_once $base . '/PHPMailer.php';
    require_once $base . '/SMTP.php';
    require_once $base . '/Exception.php';
  }
}


// Carga configuración si existe
if (file_exists(__DIR__ . '/mail_config.php')) {
  require_once __DIR__ . '/mail_config.php';
} else {
  // Defaults si no existe (desactiva envío real)
  if (!defined('SMTP_ENABLED')) define('SMTP_ENABLED', false);
  if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
  if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
  if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls');
  if (!defined('SMTP_USER')) define('SMTP_USER', '');
  if (!defined('SMTP_PASS')) define('SMTP_PASS', '');
  if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'no-reply@example.com');
  if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'AlertARD');
}

function send_mail_smart($toEmail, $subject, $htmlBody) {
  // Si no hay envío configurado, no fallar en local: devuelve false pero no rompe
  $sent = false;
  // Si PHPMailer está disponible, úsalo
  if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    try {
      $mail = new PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = SMTP_HOST;
      $mail->Port = SMTP_PORT;
      $mail->SMTPAuth = true;
      $mail->Username = SMTP_USER;
      $mail->Password = SMTP_PASS;
      if (SMTP_SECURE === 'ssl') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      else $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
      $mail->addAddress($toEmail);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $htmlBody;
      $mail->AltBody = strip_tags($htmlBody);
      $sent = $mail->send();
    } catch (Throwable $e) {
      $sent = false;
    }
  } else {
    // Fallback simple: mail()
    if (SMTP_ENABLED === false) {
      // En local sin SMTP, no intentes mail() para evitar fallos; regresa false.
      return false;
    }
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $sent = @mail($toEmail, $subject, $htmlBody, $headers);
  }
  return $sent;
}
