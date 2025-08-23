<?php
// api/super_types_list.php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

start_session_safe();

// Sólo validator/admin (quita esto si quieres que cualquiera lo lea)
$role = $_SESSION['role'] ?? 'guest';
if (!in_array($role, ['validator','admin'], true)) {
  json_out(['ok' => false, 'error' => 'No autorizado'], 403);
}

try {
  $pdo = dbx();

  //  Ajusta el nombre de tabla si en tu esquema se llama distinto
  $rows = $pdo->query("SELECT id, name FROM incident_types ORDER BY name ASC")->fetchAll();

  json_out(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
  json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}
