<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$data = $_POST + body_json();
$id = (int)($data['id'] ?? 0);
if (!$id) json_out(['error'=>'missing id'], 400);

try {
  $pdo->beginTransaction();

  // Carga corrección (debe estar pendiente)
  $c = $pdo->prepare("SELECT * FROM incident_corrections WHERE id=? AND status='pending'");
  $c->execute([$id]);
  $corr = $c->fetch();
  if (!$corr) { $pdo->rollBack(); json_out(['error'=>'not found or not pending'], 404); }

  $incident_id = (int)$corr['incident_id'];

  // Construye UPDATE dinámico con lo que venga en la corrección
  $sets = [];
  $params = [];

  if ($corr['new_deaths'] !== null)            { $sets[]="deaths=?";            $params[]=(int)$corr['new_deaths']; }
  if ($corr['new_injuries'] !== null)          { $sets[]="injuries=?";          $params[]=(int)$corr['new_injuries']; }
  if ($corr['new_loss_estimate_rd'] !== null)  { $sets[]="loss_estimate_rd=?";  $params[]= (float)$corr['new_loss_estimate_rd']; }
  if ($corr['new_latitude'] !== null)          { $sets[]="latitude=?";          $params[]= (float)$corr['new_latitude']; }
  if ($corr['new_longitude'] !== null)         { $sets[]="longitude=?";         $params[]= (float)$corr['new_longitude']; }
  if ($corr['new_province_id'] !== null)       { $sets[]="province_id=?";       $params[]=(int)$corr['new_province_id']; }
  if ($corr['new_municipality_id'] !== null)   { $sets[]="municipality_id=?";   $params[]=(int)$corr['new_municipality_id']; }
  if ($corr['new_barrio_id'] !== null)         { $sets[]="barrio_id=?";         $params[]=(int)$corr['new_barrio_id']; }

  if (!empty($sets)) {
    $sql = "UPDATE incidents SET ".implode(',',$sets)." WHERE id=?";
    $params[] = $incident_id;
    $u = $pdo->prepare($sql);
    $u->execute($params);
  }

  // Marca corrección como aplicada
  $u2 = $pdo->prepare("UPDATE incident_corrections SET status='applied' WHERE id=?");
  $u2->execute([$id]);

  $pdo->commit();
  json_out(['ok'=>true, 'updated_fields'=>count($sets)]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
