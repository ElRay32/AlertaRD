<?php
require __DIR__.'/db.php';
header('Content-Type: application/json; charset=utf-8');

function ok($a=[]){ echo json_encode(['ok'=>true]+$a, JSON_UNESCAPED_UNICODE); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$entity = trim($_POST['entity'] ?? '');
$id     = (int)($_POST['id'] ?? 0);
$name   = trim($_POST['name'] ?? '');
$province_id     = (int)($_POST['province_id'] ?? 0);
$municipality_id = (int)($_POST['municipality_id'] ?? 0);

if ($name==='') err('Falta nombre');

try {
  if ($entity==='municipalities') {
    // único por (province_id, name)
    $st = $pdo->prepare("SELECT id FROM municipalities WHERE name=? AND province_id=? AND id<>?");
    $st->execute([$name,$province_id,$id]);
    if ($st->fetch()) err('Ya existe un municipio con ese nombre en esa provincia', 409);

    if ($id) {
      $pdo->prepare("UPDATE municipalities SET name=?, province_id=? WHERE id=?")->execute([$name,$province_id,$id]);
    } else {
      $pdo->prepare("INSERT INTO municipalities(name, province_id) VALUES(?,?)")->execute([$name,$province_id]);
    }
    ok();
  }

  if ($entity==='barrios') {
    // único por (municipality_id, name)
    $table = 'barrios'; // cambia si se llama distinto
    $st = $pdo->prepare("SELECT id FROM `$table` WHERE name=? AND municipality_id=? AND id<>?");
    $st->execute([$name,$municipality_id,$id]);
    if ($st->fetch()) err('Ya existe un barrio con ese nombre en ese municipio', 409);

    if ($id) {
      $pdo->prepare("UPDATE `$table` SET name=?, municipality_id=? WHERE id=?")->execute([$name,$municipality_id,$id]);
    } else {
      $pdo->prepare("INSERT INTO `$table`(name, municipality_id) VALUES(?,?)")->execute([$name,$municipality_id]);
    }
    ok();
  }

  if ($entity==='provinces') {
    $st = $pdo->prepare("SELECT id FROM provinces WHERE name=? AND id<>?");
    $st->execute([$name,$id]);
    if ($st->fetch()) err('Ya existe una provincia con ese nombre', 409);

    if ($id) $pdo->prepare("UPDATE provinces SET name=? WHERE id=?")->execute([$name,$id]);
    else     $pdo->prepare("INSERT INTO provinces(name) VALUES(?)")->execute([$name]);
    ok();
  }

  err('Entidad no soportada',400);
} catch (Throwable $e) {
  err($e->getMessage(),500);
}
