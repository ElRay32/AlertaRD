<?php
require __DIR__.'/db.php';
header('Content-Type: application/json; charset=utf-8');

function ok($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function fail($msg,$code=500){ http_response_code($code); ok(['ok'=>false,'error'=>$msg]); }

function hasTable(PDO $pdo, string $t): bool {
  $st=$pdo->prepare("SELECT 1 FROM information_schema.tables
                     WHERE table_schema=DATABASE() AND table_name=?");
  $st->execute([$t]); return (bool)$st->fetchColumn();
}

/** Busca, en una tabla, una columna que “parezca” la relación pedida. */
function findRelCol(PDO $pdo, string $table, array $candidates): ?string {
  $st=$pdo->prepare("SELECT column_name FROM information_schema.columns
                     WHERE table_schema=DATABASE() AND table_name=?");
  $st->execute([$table]);
  $cols = array_map('strtolower', array_column($st->fetchAll(), 'column_name'));

  // 1) primero exactos
  foreach ($candidates as $c) if (in_array(strtolower($c), $cols, true)) return $c;
  // 2) luego por “contiene” (provincia, province, municipio, municipality)
  foreach ($cols as $c) {
    foreach ($candidates as $cand) {
      $needle = strtolower(str_replace(['_id','id_'],'',$cand)); // province, provincia, municipality, municipio
      if (str_contains($c, $needle)) return $c;
    }
  }
  return null;
}

$entity          = trim($_GET['entity'] ?? '');
$province_id     = (int)($_GET['province_id'] ?? 0);
$municipality_id = (int)($_GET['municipality_id'] ?? 0);
$debug           = isset($_GET['debug']);

try {
  switch ($entity) {
    case 'provinces': {
      $table = hasTable($pdo,'provinces') ? 'provinces' : (hasTable($pdo,'provincies') ? 'provincies' : null);
      if (!$table) ok(['ok'=>true,'data'=>[], 'warning'=>'missing provinces table']);
      $rows = $pdo->query("SELECT id, name FROM `$table` ORDER BY name")->fetchAll();
      ok(['ok'=>true,'data'=>$rows, 'debug'=>$debug?['table'=>$table,'rows'=>count($rows)]:null]);
    }

    case 'types': { // tipos de incidente
      $table = hasTable($pdo,'incident_types') ? 'incident_types' : (hasTable($pdo,'types') ? 'types' : null);
      if (!$table) ok(['ok'=>true,'data'=>[], 'warning'=>'missing incident_types table']);
      $rows = $pdo->query("SELECT id, name FROM `$table` ORDER BY name")->fetchAll();
      ok(['ok'=>true,'data'=>$rows, 'debug'=>$debug?['table'=>$table,'rows'=>count($rows)]:null]);
    }

    case 'municipalities': {
      // nombres frecuentes de la tabla
      $table = null;
      foreach (['municipalities','municipios','municipality'] as $t) if (hasTable($pdo,$t)) { $table=$t; break; }
      if (!$table) ok(['ok'=>true,'data'=>[], 'warning'=>'missing municipalities table']);

      // detectar columna de relación (province_id / provincia_id / id_province / province / provincia…)
      $relCol = findRelCol($pdo, $table, ['province_id','provincia_id','id_province','province','provincia']);
      if ($province_id <= 0 || !$relCol) {
        // sin provincia o no hay columna -> devolver todos, no 400
        $rows = $pdo->query("SELECT id, name FROM `$table` ORDER BY name")->fetchAll();
        ok(['ok'=>true,'data'=>$rows, 'debug'=>$debug?['table'=>$table,'relCol'=>$relCol,'filter'=>false,'rows'=>count($rows)]:null]);
      }

      $sql = "SELECT id, name FROM `$table` WHERE `$relCol`=? ORDER BY name";
      $st = $pdo->prepare($sql); $st->execute([$province_id]);
      $rows = $st->fetchAll();
      ok(['ok'=>true,'data'=>$rows, 'debug'=>$debug?['table'=>$table,'relCol'=>$relCol,'filter'=>['col'=>$relCol,'value'=>$province_id],'rows'=>count($rows)]:null]);
    }

    case 'barrios': {
      // detectar tabla posible
      $table = null;
      foreach (['barrios','neighborhoods','sectors','sectores','districts'] as $t) if (hasTable($pdo,$t)) { $table=$t; break; }
      if (!$table) ok(['ok'=>true,'data'=>[], 'warning'=>'missing barrios/sectors table']);

      // detectar columna de relación hacia municipio
      $relCol = findRelCol($pdo, $table, ['municipality_id','municipio_id','id_municipality','municipality','municipio']);
      if ($municipality_id <= 0 || !$relCol) {
        // sin municipio o sin columna -> devolver todos
        $rows = $pdo->query("SELECT id, name FROM `$table` ORDER BY name")->fetchAll();
        ok(['ok'=>true,'data'=>$rows, 'debug'=>$debug?['table'=>$table,'relCol'=>$relCol,'filter'=>false,'rows'=>count($rows)]:null]);
      }

      $sql = "SELECT id, name FROM `$table` WHERE `$relCol`=? ORDER BY name";
      $st = $pdo->prepare($sql); $st->execute([$municipality_id]);
      $rows = $st->fetchAll();
      ok(['ok'=>true,'data'=>$rows, 'debug'=>$debug?['table'=>$table,'relCol'=>$relCol,'filter'=>['col'=>$relCol,'value'=>$municipality_id],'rows'=>count($rows)]:null]);
    }

    default:
      fail('unknown entity', 400);
  }
} catch (Throwable $e) {
  fail($e->getMessage(), 500);
}
