<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();
require_role(['admin','validator']); // cambia si quieres solo admin

$id   = (int)($_POST['id'] ?? 0);
$hard = (int)($_POST['hard'] ?? 0); // 0 = soft (status='deleted'), 1 = hard (borra filas)
if (!$id) json_out(['ok'=>false,'error'=>'missing id'], 400);

try {
  $pdo->beginTransaction();

  if ($hard) {
    // 1) borrar fotos físicas y filas
    $st = $pdo->prepare("SELECT id, path_or_url FROM incident_photos WHERE incident_id=?");
    $st->execute([$id]);
    foreach ($st->fetchAll() as $ph) {
      $p = $ph['path_or_url'];
      if ($p && str_starts_with($p, '/')) {
        $abs = realpath(__DIR__.'/..'.$p);
        if ($abs && is_file($abs)) @unlink($abs);
      }
    }
    $pdo->prepare("DELETE FROM incident_photos WHERE incident_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM incident_incident_type WHERE incident_id=?")->execute([$id]);
    // elimina la incidencia
    $del = $pdo->prepare("DELETE FROM incidents WHERE id=?");
    $del->execute([$id]);
    $affected = $del->rowCount();
    $pdo->commit();
    json_out(['ok'=>true,'hard'=>1,'affected'=>$affected]);
  } else {
    // 2) soft delete
    $upd = $pdo->prepare("UPDATE incidents SET status='deleted', updated_at=NOW() WHERE id=?");
    $upd->execute([$id]);
    $affected = $upd->rowCount();
    $pdo->commit();
    json_out(['ok'=>true,'hard'=>0,'affected'=>$affected]);
  }

} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
