<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$q            = trim($_GET['q'] ?? '');
$province_id  = (int)($_GET['province_id'] ?? 0);
$type_id      = (int)($_GET['type_id'] ?? 0);
$date_from    = $_GET['date_from'] ?? null; // YYYY-MM-DD
$date_to      = $_GET['date_to'] ?? null;   // YYYY-MM-DD
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset       = ($page - 1) * $limit;

$where = ["i.status='published'"];
$params = [];
$selectScore = "0 AS score";
$orderBy = "i.occurrence_at DESC";

// ¿Usamos FULLTEXT? (si q tiene ≥4 caracteres)
$useFTS = (mb_strlen($q) >= 4);

if ($q !== '') {
  if ($useFTS) {
    $where[] = "MATCH(i.title,i.description) AGAINST (? IN BOOLEAN MODE)";
    // añadimos '*' para prefijo (boolean mode)
    $params[] = $q . '*';
    $selectScore = "MATCH(i.title,i.description) AGAINST (". $pdo->quote($q . '*') ." IN BOOLEAN MODE) AS score";
    $orderBy = "score DESC, i.occurrence_at DESC";
  } else {
    // fallback LIKE (corto)
    $where[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
  }
}

if ($province_id) { $where[] = "i.province_id = ?"; $params[] = $province_id; }
if ($type_id)     { $where[] = "EXISTS(SELECT 1 FROM incident_incident_type iit WHERE iit.incident_id=i.id AND iit.type_id=?)"; $params[] = $type_id; }
if ($date_from)   { $where[] = "i.occurrence_at >= ?"; $params[] = $date_from.' 00:00:00'; }
if ($date_to)     { $where[] = "i.occurrence_at <= ?"; $params[] = $date_to.' 23:59:59'; }

$where_sql = 'WHERE '.implode(' AND ', $where);

$sql = "
SELECT i.id, i.title, i.description, i.occurrence_at,
       p.name AS province, m.name AS municipality, b.name AS barrio,
       i.latitude, i.longitude,
       GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ') AS types,
       (SELECT COUNT(*) FROM incident_photos ph WHERE ph.incident_id=i.id) AS photos_count,
       {$selectScore}
FROM incidents i
LEFT JOIN provinces p ON p.id=i.province_id
LEFT JOIN municipalities m ON m.id=i.municipality_id
LEFT JOIN barrios b ON b.id=i.barrio_id
LEFT JOIN incident_incident_type iit ON iit.incident_id=i.id
LEFT JOIN incident_types it ON it.id=iit.type_id
{$where_sql}
GROUP BY i.id
ORDER BY {$orderBy}
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
