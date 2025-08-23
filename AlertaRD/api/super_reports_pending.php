<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$q         = trim($_GET['q'] ?? '');
$date_from = $_GET['date_from'] ?? null; // YYYY-MM-DD
$date_to   = $_GET['date_to'] ?? null;   // YYYY-MM-DD
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset    = ($page - 1) * $limit;

$where = ["i.status='pending'"];
$params = [];
$useFTS = (mb_strlen($q) >= 4);

if ($q !== '') {
  if ($useFTS) {
    $where[] = "MATCH(i.title,i.description) AGAINST (? IN BOOLEAN MODE)";
    $params[] = $q . '*';
  } else {
    $where[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
  }
}
if ($date_from) { $where[] = "i.occurrence_at >= ?"; $params[] = $date_from.' 00:00:00'; }
if ($date_to)   { $where[] = "i.occurrence_at <= ?"; $params[] = $date_to.' 23:59:59'; }


$where_sql = 'WHERE '.implode(' AND ', $where);

$sql = "
SELECT
  i.id, i.title, i.occurrence_at, i.created_at,
  p.name AS province, m.name AS municipality,
  t.types
FROM incidents i
LEFT JOIN provinces      p ON p.id = i.province_id
LEFT JOIN municipalities m ON m.id = i.municipality_id
LEFT JOIN (
  SELECT iit.incident_id,
         GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ') AS types
  FROM incident_incident_type iit
  JOIN incident_types it ON it.id = iit.type_id
  GROUP BY iit.incident_id
) t ON t.incident_id = i.id
{$where_sql}
ORDER BY i.occurrence_at DESC
LIMIT {$limit} OFFSET {$offset}
";

$count_sql = "SELECT COUNT(*) FROM incidents i {$where_sql}";

try {
  $stmt = $pdo->prepare($sql);   $stmt->execute($params);
  $rows = $stmt->fetchAll();

  $c = $pdo->prepare($count_sql); $c->execute($params);
  $total = (int)$c->fetchColumn();

  json_out(['data'=>$rows, 'page'=>$page, 'limit'=>$limit, 'total'=>$total]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
