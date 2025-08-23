<?php
// api/super_types_save.php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

start_session_safe();
require_csrf();

// Sólo validator/admin
$role = $_SESSION['role'] ?? 'guest';
if (!in_array($role, ['validator','admin'], true)) {
  json_out(['ok' => false, 'error' => 'No autorizado'], 403);
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
  json_out(['ok' => false, 'error' => 'Nombre requerido'], 422);
}

try {
  $pdo = dbx();

  // ⚠️ Ajusta el nombre de tabla si en tu esquema se llama distinto
  // (ej.: tipo_incidente). De fábrica uso incident_types(name).
  $stmt = $pdo->prepare("INSERT INTO incident_types (name) VALUES (?)");
  $stmt->execute([$name]);

  json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}
