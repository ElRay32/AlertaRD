<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

// GET resource = provinces|municipalities|barrios|types
$resource = $_GET['resource'] ?? '';

try {
  if ($resource === 'provinces') {
    $stmt = $pdo->query("SELECT id, name FROM provinces ORDER BY name");
    echo json_encode($stmt->fetchAll()); exit;
  }

  if ($resource === 'types') {
    $stmt = $pdo->query("SELECT id, name FROM incident_types ORDER BY name");
    echo json_encode($stmt->fetchAll()); exit;
  }

  if ($resource === 'municipalities') {
    $province_id = (int)($_GET['province_id'] ?? 0);
    if (!$province_id) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT id, name FROM municipalities WHERE province_id=? ORDER BY name");
    $stmt->execute([$province_id]);
    echo json_encode($stmt->fetchAll()); exit;
  }

  if ($resource === 'barrios') {
    $municipality_id = (int)($_GET['municipality_id'] ?? 0);
    if (!$municipality_id) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT id, name FROM barrios WHERE municipality_id=? ORDER BY name");
    $stmt->execute([$municipality_id]);
    echo json_encode($stmt->fetchAll()); exit;
  }

  http_response_code(400);
  echo json_encode(['error'=>'invalid resource']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
