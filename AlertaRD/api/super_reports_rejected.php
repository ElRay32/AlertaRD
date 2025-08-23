<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);                // solo validadores/admin

header('Content-Type: application/json; charset=utf-8');

$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$conds = ["i.status='rejected'"];
$params = [];

if ($q !== '') {
  $conds[] = "(i.title LIKE ? OR i.description LIKE ?)";
  $params[] = "%$q%"; $params[] = "%$q%";
}

$where = 'WHERE '.implode(' AND ', $conds);

$countSql = "SELECT COUNT(*) FROM incidents i $where";

$listSql = "
SELECT
  i.id,
  i.title,
  i.description,
  i.occurrence_at,
  i.created_at,
  p.name AS province,
  m.name AS municipality,
  (SELECT GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ')
     FROM incident_incident_type iit
     JOIN incident_types it ON it.id = iit.type_id
     WHERE iit.incident_id = i.id) AS types
FROM incidents i
LEFT JOIN provinces      p ON p.id = i.province_id
LEFT JOIN municipalities m ON m.id = i.municipality_id
$where
ORDER BY i.created_at DESC
LIMIT {$limit} OFFSET {$offset}
";

try {
  $st = $pdo->prepare($listSql);  $st->execute($params);  $rows = $st->fetchAll();
  $ct = $pdo->prepare($countSql); $ct->execute($params);  $total = (int)$ct->fetchColumn();
  json_out(['data'=>$rows, 'page'=>$page, 'limit'=>$limit, 'total'=>$total]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
 