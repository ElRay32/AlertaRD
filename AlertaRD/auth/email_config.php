<?php
return [
  // dominios permitidos (puedes añadir/quitar)
  'allowed_domains' => [
    'gmail.com','googlemail.com',
    'outlook.com','hotmail.com','live.com'
  ],

  // Nombre remitente al enviar el código
  'from_name'  => 'AlertaRD',
  'from_email' => 'no-reply@localhost',

  // Expiración del código (minutos)
  'code_ttl_minutes' => 10,

  // Rate limit básico (minutos entre envíos al mismo email)
  'resend_wait_minutes' => 1,

  // Modo debug: además de enviar (si hay SMTP), MUESTRA el código en pantalla
  'debug_show_code' => true,

  // SMTP (opcional). Si lo dejas null, intentará mail()
  'smtp' => [
    // Ejemplo Mailtrap (DEV): host, port, user, pass
    // 'host' => 'smtp.mailtrap.io', 'port' => 2525, 'username' => 'xxx', 'password' => 'yyy', 'secure'=>null
    'host' => null, 'port' => null, 'username' => null, 'password' => null, 'secure' => null
  ],
];
