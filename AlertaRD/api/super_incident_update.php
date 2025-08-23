<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();
require_role(['validator','admin']);

$B = $_POST + (array)body_json();
$id = (int)($B['id'] ?? 0);
if (!$id) json_out(['error'=>'missing id'], 400);

$fields = [
  'title','description','occurrence_at',
  'province_id','municipality_id','barrio_id',
  'latitude','longitude','deaths','injuries','loss_estimate_rd'
];

$updates = []; $params = [];
foreach ($fields as $f) {
  if (array_key_exists($f, $B)) {
    $updates[] = "i.$f = ?";
    $params[]  = ($B[$f] === '' ? null : $B[$f]);
  }
}
if (!$updates) json_out(['error'=>'no fields'], 400);

try {
  $pdo->beginTransaction();

  $sql = "UPDATE incidents i SET ".implode(',', $updates).", updated_at=NOW() WHERE i.id=?";
  $params2 = $params; $params2[] = $id;
  $pdo->prepare($sql)->execute($params2);

  if (isset($B['types']) && is_array($B['types'])) {
    $pdo->prepare("DELETE FROM incident_incident_type WHERE incident_id=?")->execute([$id]);
    if ($B['types']) {
      $ins = $pdo->prepare("INSERT INTO incident_incident_type (incident_id, type_id) VALUES (?,?)");
      foreach ($B['types'] as $t) { $ins->execute([$id, (int)$t]); }
    }
  }

  $pdo->commit();
  json_out(['ok'=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
