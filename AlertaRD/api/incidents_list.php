<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

// Lista solo P U B L I C A D A S para el público
$q          = trim($_GET['q'] ?? '');
$provinceId = (int)($_GET['province_id'] ?? 0);
$typeId     = (int)($_GET['type_id'] ?? 0);
$dateFrom   = trim($_GET['date_from'] ?? '');
$dateTo     = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset     = ($page - 1) * $limit;

$conds   = ["i.status='published'"];
$params  = [];

if ($q !== '') {
  $conds[] = "(i.title LIKE ? OR i.description LIKE ?)";
  $params[] = "%$q%"; $params[] = "%$q%";
}
if ($provinceId > 0) {
  $conds[] = "i.province_id = ?";
  $params[] = $provinceId;
}
if ($typeId > 0) {
  // requiere que el incidente tenga ese tipo
  $conds[] = "EXISTS (SELECT 1 FROM incident_incident_type x WHERE x.incident_id=i.id AND x.type_id=?)";
  $params[] = $typeId;
}
if ($dateFrom !== '') {
  $conds[] = "i.occurrence_at >= ?";
  $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
  $conds[] = "i.occurrence_at < DATE_ADD(?, INTERVAL 1 DAY)";
  $params[] = $dateTo . ' 00:00:00';
}
$whereSql = $conds ? ('WHERE '.implode(' AND ', $conds)) : '';

$countSql = "SELECT COUNT(*) FROM incidents i $whereSql";
$sql = "
SELECT
  i.id,
  i.title,
  i.description,
  i.occurrence_at,
  p.name AS province,
  m.name AS municipality,
  -- primerita foto (si existe)
  (SELECT ip.path_or_url FROM incident_photos ip WHERE ip.incident_id=i.id ORDER BY ip.id ASC LIMIT 1) AS photo,
  -- lista de tipos
  (SELECT GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ')
     FROM incident_incident_type iit
     JOIN incident_types it ON it.id = iit.type_id
     WHERE iit.incident_id = i.id) AS types
FROM incidents i
LEFT JOIN provinces      p ON p.id=i.province_id
LEFT JOIN municipalities m ON m.id=i.municipality_id
$whereSql
ORDER BY i.occurrence_at DESC
LIMIT {$limit} OFFSET {$offset}
";

try {
  $st = $pdo->prepare($sql);   $st->execute($params);   $rows = $st->fetchAll();
  $ct = $pdo->prepare($countSql); $ct->execute($params); $total = (int)$ct->fetchColumn();
  json_out(['data'=>$rows, 'page'=>$page, 'limit'=>$limit, 'total'=>$total]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
