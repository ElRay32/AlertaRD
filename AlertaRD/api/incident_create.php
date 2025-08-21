<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();
require_role(['reporter','validator','admin']); // solo usuarios logueados

// Sanitiza helpers
function num_or_null($v){ return ($v === '' || $v === null) ? null : $v; }
function pos_float($v){ if ($v === '' || $v === null) return null; $f = (float)$v; return $f >= 0 ? $f : 0; }
function clamp($v, $min, $max){ if ($v === null || $v === '') return null; $f=(float)$v; return max($min, min($max,$f)); }

// Recolecta campos
$title         = trim($_POST['title'] ?? '');
$description   = trim($_POST['description'] ?? '');
$occurrence_at = trim($_POST['occurrence_at'] ?? ''); // 'YYYY-MM-DDTHH:MM'
$province_id   = num_or_null($_POST['province_id'] ?? null);
$municipality_id = num_or_null($_POST['municipality_id'] ?? null);
$barrio_id     = num_or_null($_POST['barrio_id'] ?? null);
$latitude      = clamp($_POST['latitude'] ?? null, 16.0, 21.0);  // RD aprox
$longitude     = clamp($_POST['longitude'] ?? null, -73.0, -67.0);
$deaths        = ($_POST['deaths'] ?? '')==='' ? null : max(0, (int)$_POST['deaths']);
$injuries      = ($_POST['injuries'] ?? '')==='' ? null : max(0, (int)$_POST['injuries']);
$loss          = pos_float($_POST['loss_estimate_rd'] ?? null);

// Tipos (obligatorio >=1)
$types = $_POST['types'] ?? [];
if (!is_array($types)) $types = [];

// Links opcionales
$links = $_POST['social_links'] ?? [];
if (!is_array($links)) $links = [];

$reporter_user_id = $_SESSION['user_id'] ?? null;

// Validaciones básicas
$errors = [];
if ($title === '') $errors[] = 'El título es obligatorio.';
if ($occurrence_at === '') $errors[] = 'La fecha y hora son obligatorias.';
if (count($types) < 1) $errors[] = 'Selecciona al menos un tipo.';
if ($latitude === null || $longitude === null) $errors[] = 'Debes indicar coordenadas en el mapa.';
if (!empty($errors)) { json_out(['ok'=>false, 'errors'=>$errors], 422); }

// Normaliza datetime
$occ_dt = str_replace('T', ' ', $occurrence_at).':00';

// Inserta incidente
try {
  $pdo->beginTransaction();

  $ins = $pdo->prepare("INSERT INTO incidents
    (title, description, occurrence_at, province_id, municipality_id, barrio_id,
     latitude, longitude, deaths, injuries, loss_estimate_rd,
     status, reporter_user_id, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
  $ins->execute([
    $title, $description, $occ_dt, $province_id, $municipality_id, $barrio_id,
    $latitude, $longitude, $deaths, $injuries, $loss,
    'pending', $reporter_user_id
  ]);
  $incident_id = (int)$pdo->lastInsertId();

  // Tipos
  $it = $pdo->prepare("INSERT INTO incident_incident_type(incident_id, type_id) VALUES(?,?)");
  foreach ($types as $t) {
    $tid = (int)$t; if ($tid>0) $it->execute([$incident_id, $tid]);
  }

  // Links
  if (!empty($links)) {
    $il = $pdo->prepare("INSERT INTO incident_social_links(incident_id, platform, url) VALUES (?,?,?)");
    foreach ($links as $url) {
      $url = trim($url);
      if ($url === '') continue;
      if (!filter_var($url, FILTER_VALIDATE_URL)) continue;
      // plataforma simple por dominio
      $host = parse_url($url, PHP_URL_HOST) ?: '';
      $platform = str_ireplace(['www.','.com','.net','.org'], '', $host);
      $il->execute([$incident_id, $platform, $url]);
    }
  }

  // Fotos (opcional)
  if (!empty($_FILES['photos']['name'][0])) {
    $dir = __DIR__ . '/../uploads/incidents';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $max_mb  = 10;

    $pf = $pdo->prepare("INSERT INTO incident_photos(incident_id, path_or_url) VALUES (?,?)");

    foreach ($_FILES['photos']['name'] as $idx => $name) {
      $tmp  = $_FILES['photos']['tmp_name'][$idx];
      $type = $_FILES['photos']['type'][$idx] ?? '';
      $err  = $_FILES['photos']['error'][$idx] ?? UPLOAD_ERR_OK;
      $size = $_FILES['photos']['size'][$idx] ?? 0;
      if ($err !== UPLOAD_ERR_OK || !in_array($type, $allowed, true) || $size > $max_mb*1024*1024) continue;

      $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
      $fname = 'inc_'.$incident_id.'_'.bin2hex(random_bytes(6)).'.'.$ext;
      $dest = $dir . '/' . $fname;
      if (move_uploaded_file($tmp, $dest)) {
        $rel = '/alertard/uploads/incidents/'.$fname;
        $pf->execute([$incident_id, $rel]);
      }
    }
  }

  $pdo->commit();
  json_out(['ok'=>true, 'id'=>$incident_id, 'status'=>'pending']);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['ok'=>false, 'error'=>$e->getMessage()], 500);
}
