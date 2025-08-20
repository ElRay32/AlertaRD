<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$data = $_POST + body_json();
$parent = (int)($data['parent_id'] ?? 0);
$child  = (int)($data['child_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;
if (!$parent || !$child) json_out(['error'=>'missing ids'], 400);

try {
  $pdo->beginTransaction();

  $pdo->prepare("UPDATE incidents SET status='merged', canonical_parent_id=? WHERE id=?")->execute([$parent,$child]);
  $pdo->prepare("INSERT IGNORE INTO incident_merge(parent_incident_id,child_incident_id,merged_by,note) VALUES (?,?,?,?)")
      ->execute([$parent,$child,$user_id,'merge from /super']);

  foreach (['incident_comments','incident_photos','incident_social_links'] as $t) {
    $pdo->prepare("UPDATE $t SET incident_id=? WHERE incident_id=?")->execute([$parent,$child]);
  }

  $sql = "INSERT IGNORE INTO incident_incident_type(incident_id,type_id,is_primary)
          SELECT ?, type_id, MAX(is_primary)
          FROM incident_incident_type
          WHERE incident_id IN (?,?)
          GROUP BY type_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$parent,$parent,$child]);

  $pdo->commit();
  json_out(['ok'=>true]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
