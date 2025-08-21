<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

try {
  $sql = "SELECT it.name AS type_name, COUNT(*) AS total
          FROM incidents i
          JOIN incident_incident_type iit ON i.id=iit.incident_id
          JOIN incident_types it ON it.id=iit.type_id
          WHERE i.status='published'
            AND i.occurrence_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          GROUP BY it.id, it.name
          ORDER BY total DESC";
  $rows = $pdo->query($sql)->fetchAll();
  json_out(['data'=>$rows]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
