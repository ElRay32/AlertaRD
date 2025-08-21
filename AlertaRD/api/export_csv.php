<?php
// /alertard/api/export_csv.php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

set_time_limit(0);

// -------- Filtros ----------
$q           = trim($_GET['q'] ?? '');
$province_id = (int)($_GET['province_id'] ?? 0);
$type_id     = (int)($_GET['type_id'] ?? 0);
$date_from   = $_GET['date_from'] ?? null; // YYYY-MM-DD
$date_to     = $_GET['date_to'] ?? null;   // YYYY-MM-DD
$status      = $_GET['status'] ?? '';      // '', 'pending','published','merged','rejected','applied'
$has_coords  = isset($_GET['has_coords']) ? (int)$_GET['has_coords'] : -1; // -1 = todos, 1 = solo con coords, 0 = solo sin coords

$where = [];
$params = [];

// Búsqueda
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
if ($status !== '') {
  $where[] = "i.status = ?";
  $params[] = $status;
}
if ($has_coords === 1)      { $where[] = "i.latitude IS NOT NULL AND i.longitude IS NOT NULL"; }
elseif ($has_coords === 0)  { $where[] = "(i.latitude IS NULL OR i.longitude IS NULL)"; }

$where_sql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// -------- Consulta ----------
$sql = "
SELECT
  i.id, i.title, i.description, i.occurrence_at, i.status,
  p.name AS province, m.name AS municipality, b.name AS barrio,
  i.latitude, i.longitude, i.deaths, i.injuries, i.loss_estimate_rd,
  r.name AS reporter_name, v.name AS validator_name, i.validated_at,
  -- concat de tipos
  GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ') AS types,
  -- contadores por subconsulta
  (SELECT COUNT(*) FROM incident_comments c WHERE c.incident_id=i.id AND c.status='visible') AS comments_count,
  (SELECT COUNT(*) FROM incident_photos   ph WHERE ph.incident_id=i.id) AS photos_count,
  (SELECT COUNT(*) FROM incident_social_links sl WHERE sl.incident_id=i.id) AS links_count
FROM incidents i
LEFT JOIN provinces p       ON p.id=i.province_id
LEFT JOIN municipalities m  ON m.id=i.municipality_id
LEFT JOIN barrios b         ON b.id=i.barrio_id
LEFT JOIN users r           ON r.id=i.reporter_user_id
LEFT JOIN users v           ON v.id=i.validated_by
LEFT JOIN incident_incident_type iit ON iit.incident_id=i.id
LEFT JOIN incident_types it         ON it.id=iit.type_id
$where_sql
GROUP BY i.id
ORDER BY i.occurrence_at DESC
";

// -------- Salida CSV ----------
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $filename = 'alertard_incidencias_' . date('Ymd_His') . '.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  // BOM para Excel (UTF-8)
  echo "\xEF\xBB\xBF";

  $out = fopen('php://output', 'w');

  // Encabezados
  fputcsv($out, [
    'id','title','description','occurrence_at','status',
    'province','municipality','barrio',
    'latitude','longitude','deaths','injuries','loss_estimate_rd',
    'types','reporter_name','validator_name','validated_at',
    'comments_count','photos_count','links_count'
  ]);

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Sanea posibles inyecciones de fórmulas en Excel
    foreach (['title','description','province','municipality','barrio','types','reporter_name','validator_name'] as $k) {
      if (isset($row[$k]) && is_string($row[$k]) && preg_match('/^[=\-+@]/', $row[$k])) {
        $row[$k] = "'".$row[$k];
      }
    }
    fputcsv($out, [
      $row['id'], $row['title'], $row['description'], $row['occurrence_at'], $row['status'],
      $row['province'], $row['municipality'], $row['barrio'],
      $row['latitude'], $row['longitude'], $row['deaths'], $row['injuries'], $row['loss_estimate_rd'],
      $row['types'], $row['reporter_name'], $row['validator_name'], $row['validated_at'],
      $row['comments_count'], $row['photos_count'], $row['links_count']
    ]);
  }
  fclose($out);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Error al exportar CSV\n\n";
  echo $e->getMessage();
  exit;
}
