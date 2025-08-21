<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

function detect_delimiter($line) {
  $c = substr_count($line, ',');
  $s = substr_count($line, ';');
  $t = substr_count($line, "\t");
  $p = substr_count($line, '|');
  $max = max($c,$s,$t,$p);
  if ($max === $s) return ';';
  if ($max === $t) return "\t";
  if ($max === $p) return '|';
  return ','; // default
}
function norm_header($h) {
  $h = trim($h);
  $h = strtolower($h);
  $h = preg_replace('/\s+/', '_', $h);
  $h = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $h);
  $h = preg_replace('/[^a-z0-9_]/', '', $h);
  return $h;
}
function title_case($s) {
  if ($s === null) return null;
  $s = trim($s);
  if ($s === '') return null;
  return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
}

$resource = $_POST['resource'] ?? $_GET['resource'] ?? '';
if (!in_array($resource, ['provinces','municipalities','barrios'])) {
  json_out(['error'=>'invalid resource'], 400);
}
if (empty($_FILES['file']['tmp_name'])) {
  json_out(['error'=>'file required (multipart form-data, name=file)'], 400);
}

// Lee archivo completo
$tmp = $_FILES['file']['tmp_name'];
$raw = file_get_contents($tmp);
if ($raw === false) json_out(['error'=>'cannot read file'], 400);

// Intenta normalizar encoding a UTF-8
$encoding = mb_detect_encoding($raw, ['UTF-8','ISO-8859-1','Windows-1252'], true) ?: 'UTF-8';
if ($encoding !== 'UTF-8') {
  $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
}

$lines = preg_split("/\r\n|\n|\r/", $raw, -1, PREG_SPLIT_NO_EMPTY);
if (count($lines) < 1) json_out(['error'=>'empty file'], 400);

$delim = detect_delimiter($lines[0]);

// Parse header
$header = str_getcsv($lines[0], $delim);
$header = array_map('norm_header', $header);
$rows = array_slice($lines, 1);

// Mapas de columnas esperadas por recurso
// Provinces: name
// Municipalities: name, province_id OR province
// Barrios: name, municipality_id OR (municipality, province)
$stats = ['processed'=>0,'inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>[]];

$pdo->beginTransaction();
try {
  foreach ($rows as $i => $line) {
    if (trim($line) === '') { continue; }
    $csv = str_getcsv($line, $delim);
    // rellena faltantes
    while (count($csv) < count($header)) $csv[] = '';

    $row = [];
    foreach ($header as $idx => $key) {
      $row[$key] = isset($csv[$idx]) ? trim($csv[$idx]) : null;
    }

    $stats['processed']++;

    if ($resource === 'provinces') {
      $name = title_case($row['name'] ?? null);
      if (!$name) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": name requerido"; continue; }

      // Try upsert
      try {
        // Si hay índice único
        $stmt = $pdo->prepare("INSERT INTO provinces(name) VALUES (?)
          ON DUPLICATE KEY UPDATE name=VALUES(name)");
        $stmt->execute([$name]);
        $stats['inserted'] += $stmt->rowCount() === 1 ? 1 : 0; // 1=insert, 2=update (MySQL devuelve 2 en algunos motores)
        if ($stmt->rowCount() === 2) $stats['updated']++;
      } catch (PDOException $e) {
        // Sin índice único: manual
        if ($e->getCode() !== '23000') {
          throw $e;
        }
        $sel = $pdo->prepare("SELECT id FROM provinces WHERE name=?");
        $sel->execute([$name]);
        $ex = $sel->fetchColumn();
        if ($ex) {
          $upd = $pdo->prepare("UPDATE provinces SET name=? WHERE id=?");
          $upd->execute([$name, $ex]);
          $stats['updated']++;
        } else {
          $ins = $pdo->prepare("INSERT INTO provinces(name) VALUES (?)");
          $ins->execute([$name]);
          $stats['inserted']++;
        }
      }

    } elseif ($resource === 'municipalities') {
      $name = title_case($row['name'] ?? null);
      $province_id = isset($row['province_id']) && $row['province_id'] !== '' ? (int)$row['province_id'] : null;
      $province_name = $row['province'] ?? null;

      if (!$name) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": name requerido"; continue; }

      if (!$province_id) {
        if (!$province_name) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": province_id o province requerido"; continue; }
        $province_name = title_case($province_name);
        $q = $pdo->prepare("SELECT id FROM provinces WHERE name=?");
        $q->execute([$province_name]);
        $province_id = $q->fetchColumn();
        if (!$province_id) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": provincia '$province_name' no encontrada"; continue; }
      }

      try {
        $stmt = $pdo->prepare("INSERT INTO municipalities(province_id,name) VALUES (?,?)
          ON DUPLICATE KEY UPDATE province_id=VALUES(province_id), name=VALUES(name)");
        $stmt->execute([$province_id,$name]);
        $stats['inserted'] += $stmt->rowCount() === 1 ? 1 : 0;
        if ($stmt->rowCount() === 2) $stats['updated']++;
      } catch (PDOException $e) {
        if ($e->getCode() !== '23000') throw $e;
        $sel = $pdo->prepare("SELECT id FROM municipalities WHERE province_id=? AND name=?");
        $sel->execute([$province_id,$name]);
        $ex = $sel->fetchColumn();
        if ($ex) {
          $upd = $pdo->prepare("UPDATE municipalities SET name=?, province_id=? WHERE id=?");
          $upd->execute([$name,$province_id,$ex]);
          $stats['updated']++;
        } else {
          $ins = $pdo->prepare("INSERT INTO municipalities(province_id,name) VALUES (?,?)");
          $ins->execute([$province_id,$name]);
          $stats['inserted']++;
        }
      }

    } elseif ($resource === 'barrios') {
      $name = title_case($row['name'] ?? null);
      $municipality_id = isset($row['municipality_id']) && $row['municipality_id'] !== '' ? (int)$row['municipality_id'] : null;
      $municipality_name = $row['municipality'] ?? null;
      $province_name = $row['province'] ?? null;

      if (!$name) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": name requerido"; continue; }

      if (!$municipality_id) {
        if (!$municipality_name) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": municipality_id o municipality requerido"; continue; }
        // Si viene municipality como texto, requerimos province para desambiguar
        if (!$province_name) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": province requerido cuando se usa municipality por nombre"; continue; }
        $municipality_name = title_case($municipality_name);
        $province_name = title_case($province_name);
        $pid = $pdo->prepare("SELECT id FROM provinces WHERE name=?");
        $pid->execute([$province_name]);
        $prov_id = $pid->fetchColumn();
        if (!$prov_id) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": provincia '$province_name' no encontrada"; continue; }
        $mid = $pdo->prepare("SELECT id FROM municipalities WHERE province_id=? AND name=?");
        $mid->execute([$prov_id,$municipality_name]);
        $municipality_id = $mid->fetchColumn();
        if (!$municipality_id) { $stats['skipped']++; $stats['errors'][] = "L".($i+2).": municipio '$municipality_name' no encontrado en '$province_name'"; continue; }
      }

      try {
        $stmt = $pdo->prepare("INSERT INTO barrios(municipality_id,name) VALUES (?,?)
          ON DUPLICATE KEY UPDATE municipality_id=VALUES(municipality_id), name=VALUES(name)");
        $stmt->execute([$municipality_id,$name]);
        $stats['inserted'] += $stmt->rowCount() === 1 ? 1 : 0;
        if ($stmt->rowCount() === 2) $stats['updated']++;
      } catch (PDOException $e) {
        if ($e->getCode() !== '23000') throw $e;
        $sel = $pdo->prepare("SELECT id FROM barrios WHERE municipality_id=? AND name=?");
        $sel->execute([$municipality_id,$name]);
        $ex = $sel->fetchColumn();
        if ($ex) {
          $upd = $pdo->prepare("UPDATE barrios SET name=?, municipality_id=? WHERE id=?");
          $upd->execute([$name,$municipality_id,$ex]);
          $stats['updated']++;
        } else {
          $ins = $pdo->prepare("INSERT INTO barrios(municipality_id,name) VALUES (?,?)");
          $ins->execute([$municipality_id,$name]);
          $stats['inserted']++;
        }
      }
    }
  }

  $pdo->commit();
  // Limitar errores a 50 para no saturar respuesta
  if (count($stats['errors']) > 50) {
    $stats['errors'] = array_slice($stats['errors'], 0, 50);
    $stats['errors'][] = '...';
  }
  json_out(['ok'=>true, 'stats'=>$stats, 'delimiter'=>$delim, 'encoding'=>$encoding, 'columns'=>$header]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
