<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

try {
  $sql = "SELECT p.name AS province_name, COUNT(*) AS total
          FROM incidents i
          LEFT JOIN provinces p ON p.id = i.province_id
          WHERE i.status='published'
            AND i.occurrence_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          GROUP BY p.id, p.name
          ORDER BY total DESC
          LIMIT 10";
  $rows = $pdo->query($sql)->fetchAll();
  json_out(['data'=>$rows]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
