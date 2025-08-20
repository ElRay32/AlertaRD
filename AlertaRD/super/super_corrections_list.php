<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

try {
  $sql = "SELECT c.id, c.incident_id, c.created_at, c.note,
                 (c.new_deaths IS NOT NULL) AS f_deaths,
                 (c.new_injuries IS NOT NULL) AS f_injuries,
                 (c.new_province_id IS NOT NULL) AS f_province,
                 (c.new_municipality_id IS NOT NULL) AS f_muni,
                 (c.new_barrio_id IS NOT NULL) AS f_barrio,
                 (c.new_loss_estimate_rd IS NOT NULL) AS f_loss,
                 (c.new_latitude IS NOT NULL) AS f_lat,
                 (c.new_longitude IS NOT NULL) AS f_lng
          FROM incident_corrections c
          WHERE c.status='pending'
          ORDER BY c.id DESC";
  $rows = $pdo->query($sql)->fetchAll();
  json_out(['data'=>$rows]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
