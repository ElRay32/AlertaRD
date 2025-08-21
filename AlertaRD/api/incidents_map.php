<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

// Filtros opcionales
$q            = trim($_GET['q'] ?? '');
$province_id  = (int)($_GET['province_id'] ?? 0);
$type_id      = (int)($_GET['type_id'] ?? 0);
$date_from    = $_GET['date_from'] ?? null; // 'YYYY-MM-DD'
$date_to      = $_GET['date_to'] ?? null;   // 'YYYY-MM-DD'
$hours        = isset($_GET['hours']) ? (int)$_GET['hours'] : 24; // fallback 24h

$where = ["i.status='published'", "i.latitude IS NOT NULL", "i.longitude IS NOT NULL"];
$params = [];

if ($q !== '') {
  $where[] = "(i.title LIKE ? OR i.description LIKE ?)";
  $params[] = "%$q%"; $params[] = "%$q%";
}
if ($province_id) {
  $where[] = "i.province_id = ?";
  $params[] = $province_id;
}
if ($type_id) {
  $where[] = "EXISTS(SELECT 1 FROM incident_incident_type iit WHERE iit.incident_id=i.id AND iit.type_id=?)";
  $params[] = $type_id;
}
if ($date_from) {
  $where[] = "i.occurrence_at >= ?";
  $params[] = $date_from . " 00:00:00";
}
if ($date_to) {
  $where[] = "i.occurrence_at <= ?";
  $params[] = $date_to . " 23:59:59";
}
if (!$date_from && !$date_to) {
  // por defecto, últimas N horas
  $where[] = "i.occurrence_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
  $params[] = $hours ?: 24;
}

$where_sql = "WHERE ".implode(" AND ", $where);

$sql = "
SELECT i.id, i.title, i.description, i.latitude, i.longitude, i.deaths, i.injuries, i.loss_estimate_rd, i.occurrence_at,
       p.name AS province, m.name AS municipality, b.name AS barrio,
       GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ') AS types
FROM incidents i
LEFT JOIN provinces p ON p.id=i.province_id
LEFT JOIN municipalities m ON m.id=i.municipality_id
LEFT JOIN barrios b ON b.id=i.barrio_id
LEFT JOIN incident_incident_type iit ON iit.incident_id=i.id
LEFT JOIN incident_types it ON it.id=iit.type_id
$where_sql
GROUP BY i.id
ORDER BY i.occurrence_at DESC
LIMIT 1000
";
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
  json_out(['data'=>$rows]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage(), 'sql'=>$sql], 500);
}
