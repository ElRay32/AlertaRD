<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { json_out(['error'=>'missing id'], 400); }

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$role = $_SESSION['role'] ?? 'guest';

// guest solo ve publicados
$statusFilter = ($role === 'validator' || $role === 'admin') ? '' : " AND i.status='published'";

try {
  // usa i.* para no fallar por columnas opcionales
  $sql = "SELECT i.*, p.name AS province_name, m.name AS municipality_name
          FROM incidents i
          LEFT JOIN provinces p      ON p.id = i.province_id
          LEFT JOIN municipalities m ON m.id = i.municipality_id
          WHERE i.id = ? {$statusFilter}";
  $st = $pdo->prepare($sql);
  $st->execute([$id]);
  $incident = $st->fetch();

  if (!$incident) { json_out(['incident'=>null, 'types'=>[], 'photos'=>[]]); }

  // Tipos
  $st = $pdo->prepare("SELECT it.id, it.name
                       FROM incident_incident_type iit
                       JOIN incident_types it ON it.id = iit.type_id
                       WHERE iit.incident_id = ?
                       ORDER BY it.name");
  $st->execute([$id]);
  $types = $st->fetchAll();

  // Fotos
  $st = $pdo->prepare("SELECT id, path_or_url
                       FROM incident_photos
                       WHERE incident_id = ?
                       ORDER BY id");
  $st->execute([$id]);
  $photos = $st->fetchAll();

  json_out(['incident'=>$incident, 'types'=>$types, 'photos'=>$photos]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
