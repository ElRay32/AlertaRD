<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require __DIR__.'/../auth/email_lib.php';
require_role(['validator','admin']);

$data = $_POST + body_json();
$cid = (int)($data['id'] ?? 0);
if (!$cid) json_out(['error'=>'missing id'], 400);

try {
  $pdo->beginTransaction();
  // Trae la corrección pendiente
  $c = $pdo->prepare("SELECT * FROM incident_corrections WHERE id=? AND status='pending' FOR UPDATE");
  $c->execute([$cid]);
  $cor = $c->fetch();
  if (!$cor) { $pdo->rollBack(); json_out(['error'=>'not found or not pending'], 404); }

  // Aplica campos presentes
  $sets = []; $vals = [];
  foreach (['new_deaths'=>'deaths','new_injuries'=>'injuries','new_loss_estimate_rd'=>'loss_estimate_rd',
            'new_latitude'=>'latitude','new_longitude'=>'longitude',
            'new_province_id'=>'province_id','new_municipality_id'=>'municipality_id','new_barrio_id'=>'barrio_id'] as $src=>$dst){
    if ($cor[$src] !== null) { $sets[] = "$dst = ?"; $vals[] = $cor[$src]; }
  }
  if ($sets) {
    $sql = "UPDATE incidents SET ".implode(', ',$sets)." WHERE id=?";
    $vals[] = $cor['incident_id'];
    $u = $pdo->prepare($sql); $u->execute($vals);
  }

  // Marca corrección aplicada
  $upc = $pdo->prepare("UPDATE incident_corrections SET status='applied', validator_user_id=?, validated_at=NOW() WHERE id=?");
  $upc->execute([$_SESSION['user_id'] ?? null, $cid]);

  $pdo->commit();

  // Notifica al autor de la corrección
  @notify_correction_status($cid, 'applied', null);

  json_out(['ok'=>true]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
