<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$data = $_POST + body_json();
$resource = $data['resource'] ?? '';
$id = isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null;
$name = trim($data['name'] ?? '');

if (!in_array($resource, ['provinces','municipalities','barrios','types'])) {
  json_out(['error'=>'invalid resource'], 400);
}
if ($name === '') json_out(['error'=>'name required'], 400);

try {
  switch ($resource) {
    case 'provinces':
      if ($id) {
        $stmt = $pdo->prepare("UPDATE provinces SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);
        json_out(['ok'=>true,'action'=>'updated','id'=>$id]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO provinces(name) VALUES (?)");
        $stmt->execute([$name]);
        json_out(['ok'=>true,'action'=>'created','id'=>$pdo->lastInsertId()]);
      }
      break;

    case 'municipalities':
      $province_id = (int)($data['province_id'] ?? 0);
      if (!$province_id) json_out(['error'=>'province_id required'], 400);
      if ($id) {
        $stmt = $pdo->prepare("UPDATE municipalities SET name=?, province_id=? WHERE id=?");
        $stmt->execute([$name, $province_id, $id]);
        json_out(['ok'=>true,'action'=>'updated','id'=>$id]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO municipalities(province_id,name) VALUES (?,?)");
        $stmt->execute([$province_id, $name]);
        json_out(['ok'=>true,'action'=>'created','id'=>$pdo->lastInsertId()]);
      }
      break;

    case 'barrios':
      $municipality_id = (int)($data['municipality_id'] ?? 0);
      if (!$municipality_id) json_out(['error'=>'municipality_id required'], 400);
      if ($id) {
        $stmt = $pdo->prepare("UPDATE barrios SET name=?, municipality_id=? WHERE id=?");
        $stmt->execute([$name, $municipality_id, $id]);
        json_out(['ok'=>true,'action'=>'updated','id'=>$id]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO barrios(municipality_id,name) VALUES (?,?)");
        $stmt->execute([$municipality_id, $name]);
        json_out(['ok'=>true,'action'=>'created','id'=>$pdo->lastInsertId()]);
      }
      break;

    case 'types':
      if ($id) {
        $stmt = $pdo->prepare("UPDATE incident_types SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);
        json_out(['ok'=>true,'action'=>'updated','id'=>$id]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO incident_types(name) VALUES (?)");
        $stmt->execute([$name]);
        json_out(['ok'=>true,'action'=>'created','id'=>$pdo->lastInsertId()]);
      }
      break;
  }
} catch (PDOException $e) {
  // Maneja duplicados/unique o FK
  if ($e->getCode() == '23000') {
    json_out(['error'=>'constraint','detail'=>$e->getMessage()], 409);
  }
  json_out(['error'=>$e->getMessage()], 500);
}
