<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();

require_role(['validator','admin']);

$data = $_POST + body_json();
$primary_id = (int)($data['primary_id'] ?? 0);
$children   = $data['children'] ?? [];

$children = array_values(array_unique(array_map('intval', $children)));
$children = array_filter($children, fn($x)=>$x>0 && $x!==$primary_id);

if (!$primary_id || empty($children)) {
  json_out(['error'=>'primary_id and at least one child required'], 400);
}

try {
  $pdo->beginTransaction();

  // Asegura que el principal exista
  $s = $pdo->prepare("SELECT * FROM incidents WHERE id=? FOR UPDATE");
  $s->execute([$primary_id]);
  $primary = $s->fetch();
  if (!$primary) { $pdo->rollBack(); json_out(['error'=>'primary not found'], 404); }

  // Verifica hijos (que existan y no estén ya merged al mismo)
  $in = implode(',', array_fill(0, count($children), '?'));
  $ss = $pdo->prepare("SELECT id, deaths, injuries, loss_estimate_rd, latitude, longitude,
                              province_id, municipality_id, barrio_id
                       FROM incidents WHERE id IN ($in) AND (merged_into_id IS NULL OR merged_into_id<>?) FOR UPDATE");
  $ss->execute([...$children, $primary_id]);
  $existing = $ss->fetchAll(PDO::FETCH_ASSOC);

  if (count($existing) !== count($children)) {
    $pdo->rollBack();
    json_out(['error'=>'some children not found or already merged'], 400);
  }

  // Mueve TIPOS (INSERT IGNORE) y limpia en hijos
  $qTypesIns = $pdo->prepare("INSERT IGNORE INTO incident_incident_type(incident_id, type_id)
                              SELECT ?, type_id FROM incident_incident_type WHERE incident_id=?");
  $qTypesDel = $pdo->prepare("DELETE FROM incident_incident_type WHERE incident_id=?");

  foreach ($children as $cid) {
    $qTypesIns->execute([$primary_id, $cid]);
    $qTypesDel->execute([$cid]);
  }

  // Reasigna PHOTOS, LINKS, COMMENTS, CORRECTIONS al principal
  $tables = [
    'incident_photos',
    'incident_social_links',
    'incident_comments',
    'incident_corrections'
  ];
  foreach ($tables as $t) {
    $q = $pdo->prepare("UPDATE $t SET incident_id=? WHERE incident_id IN ($in)");
    $q->execute([$primary_id, ...$children]);
  }

  // Agrega contadores/valores: usamos MAX por defecto (evita duplicar si eran del mismo evento)
  $maxDeaths = (int)$primary['deaths'];
  $maxInj    = (int)$primary['injuries'];
  $maxLoss   = (float)$primary['loss_estimate_rd'];

  foreach ($existing as $ch) {
    if ($ch['deaths'] !== null) $maxDeaths = max($maxDeaths, (int)$ch['deaths']);
    if ($ch['injuries'] !== null) $maxInj   = max($maxInj,   (int)$ch['injuries']);
    if ($ch['loss_estimate_rd'] !== null) $maxLoss = max($maxLoss, (float)$ch['loss_estimate_rd']);
  }

  // Completa ubicación/coords si faltan en el principal (toma del primer hijo que tenga)
  $newProvince = $primary['province_id'];
  $newMuni     = $primary['municipality_id'];
  $newBarrio   = $primary['barrio_id'];
  $newLat      = $primary['latitude'];
  $newLng      = $primary['longitude'];

  foreach ($existing as $ch) {
    if ($newProvince===null && $ch['province_id']!==null)   $newProvince = $ch['province_id'];
    if ($newMuni===null && $ch['municipality_id']!==null)   $newMuni     = $ch['municipality_id'];
    if ($newBarrio===null && $ch['barrio_id']!==null)       $newBarrio   = $ch['barrio_id'];
    if ($newLat===null && $ch['latitude']!==null)           $newLat      = $ch['latitude'];
    if ($newLng===null && $ch['longitude']!==null)          $newLng      = $ch['longitude'];
  }

  $updPrim = $pdo->prepare("UPDATE incidents
                            SET deaths=?, injuries=?, loss_estimate_rd=?,
                                province_id=?, municipality_id=?, barrio_id=?,
                                latitude=?, longitude=?
                            WHERE id=?");
  $updPrim->execute([
    $maxDeaths, $maxInj, $maxLoss,
    $newProvince, $newMuni, $newBarrio,
    $newLat, $newLng,
    $primary_id
  ]);

  // Marca hijos como merged
  $mUpd = $pdo->prepare("UPDATE incidents SET status='merged', merged_into_id=? WHERE id IN ($in)");
  $mUpd->execute([$primary_id, ...$children]);

  $pdo->commit();
  json_out(['ok'=>true, 'primary_id'=>$primary_id, 'merged_count'=>count($children)]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
