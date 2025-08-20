<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$resource = $_GET['resource'] ?? 'types';

try {
  switch ($resource) {
    case 'provinces':
      $stmt = $pdo->query("SELECT id, name FROM provinces ORDER BY name");
      json_out($stmt->fetchAll());
      break;
    case 'municipalities':
      $province_id = (int)($_GET['province_id'] ?? 0);
      $stmt = $pdo->prepare("SELECT id, name FROM municipalities WHERE province_id=? ORDER BY name");
      $stmt->execute([$province_id]);
      json_out($stmt->fetchAll());
      break;
    case 'barrios':
      $municipality_id = (int)($_GET['municipality_id'] ?? 0);
      $stmt = $pdo->prepare("SELECT id, name FROM barrios WHERE municipality_id=? ORDER BY name");
      $stmt->execute([$municipality_id]);
      json_out($stmt->fetchAll());
      break;
    case 'types':
    default:
      $stmt = $pdo->query("SELECT id, name FROM incident_types ORDER BY name");
      json_out($stmt->fetchAll());
  }
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
