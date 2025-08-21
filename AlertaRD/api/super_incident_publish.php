<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require __DIR__.'/../auth/email_lib.php';
require_csrf();

require_role(['validator','admin']);

$data = $_POST + body_json();
$id = (int)($data['id'] ?? 0);
if (!$id) json_out(['error'=>'missing id'], 400);

try {
  $pdo->beginTransaction();

  // Solo si está pendiente
  $chk = $pdo->prepare("SELECT id FROM incidents WHERE id=? AND status='pending' FOR UPDATE");
  $chk->execute([$id]);
  if (!$chk->fetch()) { $pdo->rollBack(); json_out(['error'=>'not found or not pending'], 404); }

  $user_id = $_SESSION['user_id'] ?? null;

  $upd = $pdo->prepare("UPDATE incidents
                        SET status='published', validated_by=?, validated_at=NOW()
                        WHERE id=?");
  $upd->execute([$user_id, $id]);

  $pdo->commit();

// Notificar al reportero
@notify_incident_status($id, 'published', null);


  json_out(['ok'=>true]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
