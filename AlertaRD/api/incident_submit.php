<?php
// /api/incident_submit.php — Endpoint robusto para crear reportes y responder SIEMPRE JSON.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Santo_Domingo');

function jout(array $data, int $code=200): void { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

// ---- DB ----
$pdo = null; $db_err = null;
try {
  if (file_exists(__DIR__.'/db.php')) {
    require __DIR__.'/db.php';               // debe definir $pdo o dbx()
    if (isset($pdo) && $pdo instanceof PDO) {
      // ok
    } elseif (function_exists('dbx')) {
      $pdo = dbx();
    } else {
      $db_err = 'db.php no expone $pdo ni dbx()';
    }
  } else {
    $db_err = 'db.php no encontrado';
  }
} catch (Throwable $e) { $db_err = 'db.php error: '.$e->getMessage(); }

if (!$pdo) {
  try {
    $dsn  = getenv('AR_DSN')  ?: 'mysql:host=localhost;dbname=alertard;charset=utf8mb4';
    $user = getenv('AR_USER') ?: 'root';
    $pass = getenv('AR_PASS') ?: '';
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } catch (Throwable $e) {
    jout(['ok'=>false,'error'=>'DB sin conexión: '.($db_err?:'').' | '.$e->getMessage()], 500);
  }
}

// ---- Helpers ----
session_start();
function req($k, $src){ return isset($src[$k]) ? trim((string)$src[$k]) : null; }
function intval_or_null($v){ if ($v===null || $v==='') return null; $i=filter_var($v, FILTER_VALIDATE_INT); return $i===false? null : (int)$i; }
function float_or_null($v){ if ($v===null || $v==='') return null; $f=filter_var($v, FILTER_VALIDATE_FLOAT); return $f===false? null : (float)$f; }
function parse_datetime($s){ if(!$s) return null; $ts=strtotime($s); return $ts? date('Y-m-d H:i:s', $ts) : null; }

$ctype = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
$is_json_body = str_starts_with($ctype, 'application/json');

try {
  $B = [];
  if ($is_json_body) {
    $raw = file_get_contents('php://input');
    $B = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
  } else {
    $B = $_POST;
  }
} catch (Throwable $e) {
  jout(['ok'=>false, 'error'=>'JSON inválido: '.$e->getMessage()], 400);
}

// ---- Campos ----
$title = req('title', $B);
$description = req('description', $B);
$occurrence_at = parse_datetime(req('occurrence_at', $B));
$province_id = intval_or_null(req('province_id', $B));
$municipality_id = intval_or_null(req('municipality_id', $B));
$barrio_id = intval_or_null(req('barrio_id', $B));
$latitude = float_or_null(req('latitude', $B));
$longitude = float_or_null(req('longitude', $B));
$deaths = intval_or_null(req('deaths', $B));
$injuries = intval_or_null(req('injuries', $B));
$loss_estimate_rd = float_or_null(req('loss_estimate_rd', $B));

$types = [];
if (isset($B['types']) && is_array($B['types'])) $types = $B['types'];
elseif (isset($B['types[]']) && is_array($B['types[]'])) $types = $B['types[]'];
$types = array_values(array_filter(array_map('intval', (array)$types)));

$social_links = [];
if (isset($B['social_links']) && is_array($B['social_links'])) $social_links = $B['social_links'];
elseif (isset($B['social_links[]']) && is_array($B['social_links[]'])) $social_links = $B['social_links[]'];
$social_links = array_values(array_filter(array_map('trim', (array)$social_links)));

// ---- Validación mínima ----
$errors = [];
if (!$title) $errors[]='El título es obligatorio.';
if (!$occurrence_at) $errors[]='Fecha/hora inválida.';
if ($latitude===null || $longitude===null) $errors[]='Debes seleccionar la ubicación en el mapa.';
if ($errors) jout(['ok'=>false, 'errors'=>$errors], 422);

// ---- Insert principal ----
try {
  $pdo->beginTransaction();
  $stmt = $pdo->prepare("INSERT INTO incidents
    (title, description, occurrence_at, province_id, municipality_id, barrio_id, latitude, longitude,
     deaths, injuries, loss_estimate_rd, status, created_at)
     VALUES (:title,:description,:occurrence_at,:province_id,:municipality_id,:barrio_id,:latitude,:longitude,
             :deaths,:injuries,:loss_estimate_rd,'pending',NOW())");
  $stmt->execute([
    ':title'=>$title, ':description'=>$description, ':occurrence_at'=>$occurrence_at,
    ':province_id'=>$province_id, ':municipality_id'=>$municipality_id, ':barrio_id'=>$barrio_id,
    ':latitude'=>$latitude, ':longitude'=>$longitude,
    ':deaths'=>$deaths, ':injuries'=>$injuries, ':loss_estimate_rd'=>$loss_estimate_rd,
  ]);
  $incident_id = (int)$pdo->lastInsertId();

  // tipos (opcional)
  if ($types) {
    try {
      $ins = $pdo->prepare("INSERT INTO incident_incident_type (incident_id, type_id) VALUES (:i,:t)");
      foreach ($types as $t) $ins->execute([':i'=>$incident_id, ':t'=>(int)$t]);
    } catch (Throwable $e) { /* ignora si tabla no existe */ }
  }

  // enlaces (opcional)
  if ($social_links) {
    try {
      $ins = $pdo->prepare("INSERT INTO incident_social_links (incident_id, platform, url) VALUES (:i,:p,:u)");
      foreach ($social_links as $u) {
        $platform = parse_url($u, PHP_URL_HOST) ?: '';
        $ins->execute([':i'=>$incident_id, ':p'=>$platform, ':u'=>$u]);
      }
    } catch (Throwable $e) { /* ignora */ }
  }

  // fotos (solo multipart)
  if (!$is_json_body && !empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $dir = dirname(__DIR__).'/uploads/incidents';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $ins = null;
    try { $ins = $pdo->prepare("INSERT INTO incident_photos (incident_id, path_or_url, created_at) VALUES (:i,:p,NOW())"); } catch (Throwable $e) {}
    $n = count($_FILES['photos']['name']);
    for ($k=0; $k<$n; $k++) {
      if ($_FILES['photos']['error'][$k] !== UPLOAD_ERR_OK) continue;
      $tmp = $_FILES['photos']['tmp_name'][$k];
      $ext = pathinfo($_FILES['photos']['name'][$k], PATHINFO_EXTENSION) ?: 'jpg';
      $name = 'inc-'.$incident_id.'-'.date('YmdHis').'-'.sprintf('%02d',$k).'.'.$ext;
      $dest = $dir.'/'.$name;
      if (@move_uploaded_file($tmp, $dest) && $ins) {
        $rel = '/uploads/incidents/'.$name;
        try { $ins->execute([':i'=>$incident_id, ':p'=>$rel]); } catch (Throwable $e) {}
      }
    }
  }

  $pdo->commit();
  jout(['ok'=>true, 'id'=>$incident_id]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jout(['ok'=>false, 'error'=>$e->getMessage()], 500);
}
