<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();

require_role(['validator','admin']);

$data = $_POST + body_json();
$id  = (int)($data['id'] ?? 0);
$lat = isset($data['latitude'])  ? (float)$data['latitude']  : null;
$lng = isset($data['longitude']) ? (float)$data['longitude'] : null;

if (!$id || $lat===null || $lng===null) {
  json_out(['error'=>'missing fields'], 400);
}
if ($lat < 16.0 || $lat > 21.5 || $lng < -73.5 || $lng > -67.0) {
  json_out(['error'=>'coords out of bounds'], 422);
}

try {
  $upd = $pdo->prepare("UPDATE incidents
                        SET latitude=?, longitude=?, validated_by=COALESCE(validated_by,?), validated_at=COALESCE(validated_at,NOW())
                        WHERE id=?");
  $upd->execute([$lat, $lng, ($_SESSION['user_id'] ?? null), $id]);
  json_out(['ok'=>true, 'id'=>$id, 'latitude'=>$lat, 'longitude'=>$lng]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
