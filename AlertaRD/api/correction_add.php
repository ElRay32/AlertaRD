<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();

require_role(['reporter','validator','admin']);
$data = $_POST + body_json();
$incident_id = (int)($data['incident_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;
$fields = ['new_deaths','new_injuries','new_province_id','new_municipality_id','new_barrio_id','new_loss_estimate_rd','new_latitude','new_longitude'];
$cols = ["incident_id","user_id"];
$params = [$incident_id, $user_id];
$placeholders = ["?","?"];

foreach ($fields as $f) {
  if (isset($data[$f]) && $data[$f] !== '' && $data[$f] !== null) {
    $cols[] = $f;
    $placeholders[] = "?";
    $params[] = $data[$f];
  }
}
$note = trim($data['note'] ?? '');
if ($note !== '') { $cols[]="note"; $placeholders[]="?"; $params[]=$note; }

if (!$incident_id) json_out(['error'=>'missing incident_id'], 400);
if (count($params) <= 2) json_out(['error'=>'no fields to update'], 400);

$sql = "INSERT INTO incident_corrections(" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  json_out(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage(), 'sql'=>$sql], 500);
}
