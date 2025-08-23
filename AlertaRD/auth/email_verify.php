<?php
// auth/email_verify.php  (FIX: expiración validada por la BD)
require_once __DIR__ . '/../api/helpers.php';
require_once __DIR__ . '/../api/db.php';
start_session_safe();
require_csrf();

$pdo = dbx();

$email = strtolower(trim($_POST['email'] ?? ''));
$code  = trim($_POST['code'] ?? '');

if ($email === '' || $code === '' || !preg_match('/^\d{6}$/', $code)) {
  json_out(['ok' => false, 'error' => 'Datos inválidos'], 422);
}

// Buscar token NO usado y NO expirado directamente en la BD
$stmt = $pdo->prepare("
  SELECT id, code
  FROM login_tokens
  WHERE email = ? AND used = 0 AND expires_at >= NOW()
  ORDER BY id DESC
  LIMIT 1
");
$stmt->execute([$email]);
$tok = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tok) {
  json_out(['ok' => false, 'error' => 'Código expirado'], 410);
}

// Comparar código
if ($code !== $tok['code']) {
  // Opcional: incrementar intentos si la columna existe
  try {
    $pdo->prepare("UPDATE login_tokens SET verify_attempts = verify_attempts + 1, last_attempt_at = NOW() WHERE id = ?")->execute([$tok['id']]);
  } catch (Throwable $e) { /* columna opcional */ }
  json_out(['ok' => false, 'error' => 'Código incorrecto'], 401);
}

// Éxito: marcar usado
$pdo->prepare("UPDATE login_tokens SET used = 1, used_at = NOW() WHERE id = ?")->execute([$tok['id']]);

// Crear usuario si no existe
$stmt = $pdo->prepare("SELECT id, name, email, role_id, is_active FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
  $name = ucfirst(strtok($email, '@'));
  $pdo->prepare("
    INSERT INTO users (name, email, role_id, provider, is_active)
    VALUES (?, ?, 1, 'google', 1)
  ")->execute([$name, $email]);
  $userId = (int)$pdo->lastInsertId();
  $roleName = 'reporter';
} else {
  $userId = (int)$u['id'];
  $roleId  = (int)$u['role_id'];
  $roleName = ($roleId === 3 ? 'admin' : ($roleId === 2 ? 'validator' : 'reporter'));
  if (!$u['is_active']) {
    $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$userId]);
  }
}

// Iniciar sesión
$_SESSION['user_id'] = $userId;
$_SESSION['email']   = $email;
$_SESSION['name']    = $u['name'] ?? ($name ?? strtok($email, '@'));
$_SESSION['role']    = $roleName;

// Respuesta
json_out(['ok' => true, 'role' => $_SESSION['role']]);
