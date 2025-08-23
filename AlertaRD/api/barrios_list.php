<?php
// /AlertaRD/api/barrios_list.php  — lista completa de barrios/sectores con municipality_id
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
start_session_safe();
try {
  $rows = [];
  $tries = [
    'SELECT id, name, municipality_id FROM barrios ORDER BY municipality_id, name',
    'SELECT id, nombre AS name, municipio_id AS municipality_id FROM barrios ORDER BY municipio_id, nombre',
    'SELECT id, name, municipality_id FROM neighborhoods ORDER BY municipality_id, name',
    'SELECT id, nombre AS name, municipio_id AS municipality_id FROM sectores ORDER BY municipio_id, nombre',
    'SELECT id, name, municipality_id FROM sectors ORDER BY municipality_id, name',
  ];
  foreach ($tries as $sql) {
    try { $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); break; } catch (Throwable $e) {}
  }
  if (!$rows) { throw new Exception('No encuentro tabla de barrios/sectores'); }
  echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}