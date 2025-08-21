<?php
// /alertard/api/super_incident_reject.php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require __DIR__.'/../auth/email_lib.php'; // para notificar por email
require_csrf();

require_role(['validator','admin']);

$data = $_POST + body_json();
$id   = (int)($data['id'] ?? 0);
$note = trim($data['note'] ?? '');

if (!$id) { json_out(['error'=>'missing id'], 400); }

try {
  $stmt = $pdo->prepare("UPDATE incidents
                         SET status='rejected', rejection_note=?
                         WHERE id=? AND status='pending'");
  $stmt->execute([$note ?: null, $id]);

  if ($stmt->rowCount() === 0) {
    json_out(['error'=>'not found or not pending'], 404);
  }

  // Notifica al reportero
  @notify_incident_status($id, 'rejected', $note);

  json_out(['ok'=>true]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
