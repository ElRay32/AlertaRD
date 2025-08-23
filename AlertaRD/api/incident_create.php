<?php
// /AlertaRD/api/incident_create.php (fixed)
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();
require_role(['reporter','validator','admin']);

// Helpers
function val_num_or_null($v){ return ($v === '' || $v === null) ? null : (string)$v; }
function clampf($v, $min, $max){ if ($v === null || $v === '') return null; $f=(float)$v; return max($min, min($max, $f)); }

// Datos
$data = $_POST + body_json();
$title         = trim($data['title'] ?? '');
$description   = trim($data['description'] ?? '');
$occ_raw       = trim($data['occurrence_at'] ?? '');
$occurrence_at = $occ_raw ? str_replace('T',' ', $occ_raw) . (strlen($occ_raw)===16 ? ':00' : '') : null;
$province_id   = val_num_or_null($data['province_id'] ?? null);
$municipality_id = val_num_or_null($data['municipality_id'] ?? null);
$barrio_id     = val_num_or_null($data['barrio_id'] ?? null);
$latitude      = clampf($data['latitude'] ?? null, 16.0, 21.5);
$longitude     = clampf($data['longitude'] ?? null, -73.0, -67.0);
$deaths        = ($data['deaths'] ?? '')==='' ? null : max(0, (int)$data['deaths']);
$injuries      = ($data['injuries'] ?? '')==='' ? null : max(0, (int)$data['injuries']);
$loss_estimate_rd = ($data['loss_estimate_rd'] ?? '')==='' ? null : (float)$data['loss_estimate_rd'];
$types         = $data['types'] ?? $data['types[]'] ?? [];
if (!is_array($types)) $types = [$types];
$types = array_values(array_unique(array_filter(array_map('intval', $types))));
$social_links = $data['social_links'] ?? $data['social_links[]'] ?? [];
if (!is_array($social_links)) $social_links = [$social_links];
$social_links = array_values(array_unique(array_filter(array_map('trim', $social_links))));

// Validación básica
$errors = [];
if ($title === '') $errors[] = 'title required';
if (!$occurrence_at) $errors[] = 'occurrence_at required';
if ($latitude === null || $longitude === null) $errors[] = 'coords required';
if ($errors) json_out(['ok'=>false,'errors'=>$errors], 422);

$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

try {
  $pdo->beginTransaction();

  // Insert principal
  $stmt = $pdo->prepare("INSERT INTO incidents
    (title, description, occurrence_at, province_id, municipality_id, barrio_id, latitude, longitude, deaths, injuries, loss_estimate_rd, status, created_by)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending',?)");
  $stmt->execute([
    $title, $description, $occurrence_at, $province_id, $municipality_id, $barrio_id,
    $latitude, $longitude, $deaths, $injuries, $loss_estimate_rd, $user_id
  ]);
  $incident_id = (int)$pdo->lastInsertId();

  // Tipos
  if ($types) {
    $it = $pdo->prepare("INSERT IGNORE INTO incident_incident_type(incident_id, type_id) VALUES(?,?)");
    foreach ($types as $tid) { $it->execute([$incident_id, (int)$tid]); }
  }

  // Enlaces sociales
  if ($social_links) {
    $ls = $pdo->prepare("INSERT INTO incident_social_links(incident_id, platform, url) VALUES (?,?,?)");
    foreach ($social_links as $url) {
      $plat = (stripos($url,'twitter')!==false?'Twitter':
               (stripos($url,'facebook')!==false?'Facebook':
               (stripos($url,'instagram')!==false?'Instagram':
               (stripos($url,'tiktok')!==false?'TikTok':'Link'))));
      $ls->execute([$incident_id, $plat, $url]);
    }
  }

  // Fotos (subidas)
  if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
    $dir = dirname(__DIR__) . '/uploads/incidents';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $pf = $pdo->prepare("INSERT INTO incident_photos(incident_id, path_or_url) VALUES (?,?)");
    $names = $_FILES['photos']['name'];
    $tmps  = $_FILES['photos']['tmp_name'];
    $errs  = $_FILES['photos']['error'];
    for ($i=0; $i<count($names); $i++){
      if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
      $ext = pathinfo($names[$i], PATHINFO_EXTENSION);
      $fname = $incident_id.'_'.bin2hex(random_bytes(4)).($ext?'.'.$ext:'');
      $dest = $dir . '/' . $fname;
      if (@move_uploaded_file($tmps[$i], $dest)) {
        $rel = '/uploads/incidents/'.$fname; // ruta relativa
        $pf->execute([$incident_id, $rel]);
      }
    }
  }

  $pdo->commit();
  json_out(['ok'=>true, 'id'=>$incident_id, 'status'=>'pending']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok'=>false, 'error'=>$e->getMessage()], 500);
}
