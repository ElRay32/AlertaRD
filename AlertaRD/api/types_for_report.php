<?php
// /api/types_for_report.php — Devuelve [{ id, name }] para marcar en el reporte.
// Soporta tablas: types / incident_types / tipos, columnas name / nombre.
// Opcional: ?q=texto para filtrar, ?limit=200 para limitar, ?seed=1 crea 'types' con valores por defecto si no hay.

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Santo_Domingo');

function out($data, int $code=200){ http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

// --- Conexión DB: intenta usar api/db.php, si no conecta directo con DSN ---
$pdo = null; $why = null;
try {
  if (file_exists(__DIR__.'/db.php')) {
    require __DIR__.'/db.php';                 // debe exponer $pdo o dbx()
    if (isset($pdo) && $pdo instanceof PDO) { /* ok */ }
    elseif (function_exists('dbx')) { $pdo = dbx(); }
    else { $why = 'db.php no expuso $pdo ni dbx()'; }
  } else { $why = 'db.php no encontrado'; }
} catch (Throwable $e) { $why = 'db.php error: '.$e->getMessage(); }

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
    out(['ok'=>false,'error'=>'Sin conexión DB: '.($why?:'').' | '.$e->getMessage()], 500);
  }
}

// --- Parámetros ---
$q = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 0);
$limit = ($limit > 0 && $limit <= 1000) ? $limit : 0;
$seed = isset($_GET['seed']) && $_GET['seed'] !== '0';

// --- Intentar tablas/columnas conocidas ---
$cands = [
  ['tbl'=>'types',           'col'=>'name'],
  ['tbl'=>'types',           'col'=>'nombre'],
  ['tbl'=>'incident_types',  'col'=>'name'],
  ['tbl'=>'incident_types',  'col'=>'nombre'],
  ['tbl'=>'tipos',           'col'=>'name'],
  ['tbl'=>'tipos',           'col'=>'nombre'],
];

function fetchTypes(PDO $pdo, string $tbl, string $col, string $q, int $limit){
  $sql = "SELECT id, {$col} AS name FROM {$tbl}";
  $p = [];
  if ($q !== '') { $sql .= " WHERE {$col} LIKE :q"; $p[':q'] = '%'.$q.'%'; }
  $sql .= " ORDER BY {$col}";
  if ($limit) { $sql .= " LIMIT {$limit}"; }
  $st = $pdo->prepare($sql);
  $st->execute($p);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

$rows = null; $used = null;
foreach ($cands as $c) {
  try {
    $data = fetchTypes($pdo, $c['tbl'], $c['col'], $q, $limit);
    // Si la consulta ejecutó, adoptamos esa tabla (aunque esté vacía).
    $rows = $data; $used = $c; break;
  } catch (Throwable $e) {
    // probar siguiente combinación
  }
}

// Si no hay ninguna tabla válida y pidieron seed, crear 'types' y poblar
if ($rows === null && $seed) {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS types (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(120) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $defaults = ['Accidente','Robo','Incendio','Inundación','Huracán','Terremoto','Pelea','Asalto','Vandalismo','Otro'];
    $ins = $pdo->prepare("INSERT IGNORE INTO types(name) VALUES (:n)");
    foreach ($defaults as $n) { $ins->execute([':n'=>$n]); }
    $rows = fetchTypes($pdo, 'types', 'name', $q, $limit);
    $used = ['tbl'=>'types','col'=>'name'];
  } catch (Throwable $e) {
    out(['ok'=>false, 'error'=>'No hay tabla de tipos (ni se pudo crear): '.$e->getMessage()], 500);
  }
}

if ($rows === null) {
  out(['ok'=>false, 'error'=>'No se encontró tabla de tipos (types/incident_types/tipos)'], 404);
}

out(['ok'=>true, 'source'=>$used, 'count'=>count($rows), 'data'=>$rows]);
