<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) json_out(['error'=>'missing id'], 400);

try {
  $stmt = $pdo->prepare("
    SELECT i.*, p.name AS province, m.name AS municipality, b.name AS barrio
    FROM incidents i
    LEFT JOIN provinces p ON p.id=i.province_id
    LEFT JOIN municipalities m ON m.id=i.municipality_id
    LEFT JOIN barrios b ON b.id=i.barrio_id
    WHERE i.id=?
  ");
  $stmt->execute([$id]);
  $inc = $stmt->fetch();
  if (!$inc) json_out(['error'=>'not found'], 404);

  $stmt = $pdo->prepare("
    SELECT it.id, it.name FROM incident_incident_type iit
    JOIN incident_types it ON it.id=iit.type_id
    WHERE iit.incident_id=? ORDER BY it.name
  ");
  $stmt->execute([$id]);
  $types = $stmt->fetchAll();

  $stmt = $pdo->prepare("SELECT * FROM incident_photos WHERE incident_id=? ORDER BY is_cover DESC, id DESC");
  $stmt->execute([$id]);
  $photos = $stmt->fetchAll();

  $stmt = $pdo->prepare("SELECT * FROM incident_social_links WHERE incident_id=? ORDER BY id DESC");
  $stmt->execute([$id]);
  $links = $stmt->fetchAll();

  $stmt = $pdo->prepare("SELECT c.id, u.name, c.content, c.created_at
                         FROM incident_comments c JOIN users u ON u.id=c.user_id
                         WHERE c.incident_id=? AND c.status='visible' ORDER BY c.id DESC");
  $stmt->execute([$id]);
  $comments = $stmt->fetchAll();

  json_out(['incident'=>$inc, 'types'=>$types, 'photos'=>$photos, 'links'=>$links, 'comments'=>$comments]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
