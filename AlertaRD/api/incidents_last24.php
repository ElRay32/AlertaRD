<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

try {
  $stmt = $pdo->query("SELECT id, title, description, latitude, longitude, deaths, injuries, loss_estimate_rd, occurrence_at, province, municipality, barrio, types FROM vw_incidents_last_24h");
  $rows = $stmt->fetchAll();
  json_out(['data'=>$rows]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
