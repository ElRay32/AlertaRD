<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();

require_role(['validator','admin']);

$data = $_POST + body_json();
$resource = $data['resource'] ?? '';
$id = isset($data['id']) ? (int)$data['id'] : 0;

if (!in_array($resource, ['provinces','municipalities','barrios','types'])) {
  json_out(['error'=>'invalid resource'], 400);
}
if (!$id) json_out(['error'=>'id required'], 400);

try {
  switch ($resource) {
    case 'provinces':
      $stmt = $pdo->prepare("DELETE FROM provinces WHERE id=?");
      break;
    case 'municipalities':
      $stmt = $pdo->prepare("DELETE FROM municipalities WHERE id=?");
      break;
    case 'barrios':
      $stmt = $pdo->prepare("DELETE FROM barrios WHERE id=?");
      break;
    case 'types':
      $stmt = $pdo->prepare("DELETE FROM incident_types WHERE id=?");
      break;
  }
  $stmt->execute([$id]);
  json_out(['ok'=>true]);
} catch (PDOException $e) {
  if ($e->getCode() == '23000') {
    // FK en uso
    json_out(['error'=>'in_use','detail'=>'No se puede eliminar: está en uso por registros.'], 409);
  }
  json_out(['error'=>$e->getMessage()], 500);
}
