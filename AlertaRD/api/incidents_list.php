<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$q = trim($_GET['q'] ?? '');
$province_id = (int)($_GET['province_id'] ?? 0);
$type_id = (int)($_GET['type_id'] ?? 0);
$date_from = $_GET['date_from'] ?? null;
$date_to   = $_GET['date_to'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page-1)*$limit;

$where = [];
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

$where_sql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
SELECT i.id, i.title, LEFT(i.description,200) AS excerpt, i.occurrence_at, i.status,
       p.name AS province, m.name AS municipality,
       GROUP_CONCAT(it.name ORDER BY it.name SEPARATOR ', ') AS types
FROM incidents i
LEFT JOIN provinces p ON p.id=i.province_id
LEFT JOIN municipalities m ON m.id=i.municipality_id
LEFT JOIN incident_incident_type iit ON iit.incident_id=i.id
LEFT JOIN incident_types it ON it.id=iit.type_id
$where_sql
GROUP BY i.id
ORDER BY i.occurrence_at DESC
LIMIT $offset, $limit
";
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
  json_out(['data'=>$rows, 'page'=>$page, 'limit'=>$limit]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage(), 'sql'=>$sql], 500);
}
