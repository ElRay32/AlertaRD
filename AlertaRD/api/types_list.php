<?php
// /api/types_list.php — devuelve todos los tipos de incidencia, normalizando a {id, name}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
start_session_safe();
try{
  $tries = [
    'SELECT id, name FROM types ORDER BY name',
    'SELECT id, nombre AS name FROM types ORDER BY nombre',
    'SELECT id, name FROM incident_types ORDER BY name',
    'SELECT id, nombre AS name FROM incident_types ORDER BY nombre',
    'SELECT id, name FROM tipos ORDER BY name',
    'SELECT id, nombre AS name FROM tipos ORDER BY nombre',
  ];
  $rows = [];
  foreach ($tries as $sql){
    try {
      $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      if ($rows) break;
    } catch (Throwable $e) { /* probar siguiente */ }
  }
  echo json_encode(['ok'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
