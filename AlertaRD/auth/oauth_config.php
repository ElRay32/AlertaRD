<?php
// Rellena con tus credenciales (DEV: http://localhost/alertard/)
return [
  'base_url' => 'http://localhost/alertard/',

  'google' => [
    'client_id'     => 'TU_CLIENT_ID_GOOGLE.apps.googleusercontent.com',
    'client_secret' => 'TU_CLIENT_SECRET_GOOGLE',
    'redirect_uri'  => 'http://localhost/alertard/auth/google_callback.php',
    'scopes'        => ['openid','email','profile'],
    // (Opcional) limita a dominio: ejemplo 'tudominio.com'
    'allowed_domains' => []  // o ['tudominio.com']
  ],

  'microsoft' => [
    'client_id'     => 'TU_CLIENT_ID_MS',
    'client_secret' => 'TU_CLIENT_SECRET_MS',
    'redirect_uri'  => 'http://localhost/alertard/auth/ms_callback.php',
    'scopes'        => ['openid','email','profile', 'offline_access'],
    'tenant'        => 'common', // o tu tenant id
    'allowed_domains' => []      // ej. ['tudominio.com']
  ],
];
