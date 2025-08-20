<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$data = $_POST + body_json();
$id = (int)($data['id'] ?? 0);
if (!$id) json_out(['error'=>'missing id'], 400);

try {
  $stmt = $pdo->prepare("UPDATE incident_corrections SET status='rejected' WHERE id=? AND status='pending'");
  $stmt->execute([$id]);
  if ($stmt->rowCount() === 0) json_out(['error'=>'not found or not pending'], 404);
  json_out(['ok'=>true]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
