<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$data = $_POST + body_json();
$id = (int)($data['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;
if (!$id) json_out(['error'=>'missing id'], 400);

try {
  $stmt = $pdo->prepare("UPDATE incidents SET status='published', validated_by=?, validated_at=NOW() WHERE id=?");
  $stmt->execute([$user_id, $id]);
  json_out(['ok'=>true]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
