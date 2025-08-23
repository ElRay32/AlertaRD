<?php
// /AlertaRD/api/provinces_list.php  — lista completa de provincias
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
start_session_safe();
try {
  // tries: provinces / provincias(nombre)
  $rows = [];
  try {
    $rows = $pdo->query('SELECT id, name FROM provinces ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $rows = $pdo->query('SELECT id, nombre AS name FROM provincias ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
  }
  echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}