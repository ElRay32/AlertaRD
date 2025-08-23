<?php
require __DIR__.'/db.php';
header('Content-Type: application/json; charset=utf-8');

// Si usas CSRF/roles, descomenta:
 require __DIR__.'/helpers.php';
 require_csrf();
 require_role(['admin','validator']);

function json_ok($arr=[]){ echo json_encode(['ok'=>true]+$arr, JSON_UNESCAPED_UNICODE); exit; }
function json_err($msg,$code=409){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function tableExists(PDO $pdo, $name){ $st=$pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?"); $st->execute([$name]); return (bool)$st->fetchColumn(); }
function colExists(PDO $pdo,$table,$col){ $st=$pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?"); $st->execute([$table,$col]); return (bool)$st->fetchColumn(); }
function countWhere(PDO $pdo,$table,$col,$id){ if(!tableExists($pdo,$table) || !colExists($pdo,$table,$col)) return 0; $st=$pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col`=?"); $st->execute([$id]); return (int)$st->fetchColumn(); }

$entity = trim($_POST['entity'] ?? '');
$id     = (int)($_POST['id'] ?? 0);
$reassign_to = (int)($_POST['reassign_to'] ?? 0); // opcional, para reasignar y luego borrar

if (!$id) json_err('Falta id', 400);

try {
  if ($entity === 'provinces') {
    $refsM = countWhere($pdo,'municipalities','province_id',$id);
    $refsI = countWhere($pdo,'incidents','province_id',$id);
    $total = $refsM + $refsI;
    if ($total > 0 && !$reassign_to) {
      json_err("No se puede eliminar: tiene $refsM municipios y $refsI incidencias vinculadas.");
    }
    $pdo->beginTransaction();
    if ($reassign_to) {
      if (tableExists($pdo,'municipalities') && colExists($pdo,'municipalities','province_id')) {
        $pdo->prepare("UPDATE municipalities SET province_id=? WHERE province_id=?")->execute([$reassign_to,$id]);
      }
      if (tableExists($pdo,'incidents') && colExists($pdo,'incidents','province_id')) {
        $pdo->prepare("UPDATE incidents SET province_id=? WHERE province_id=?")->execute([$reassign_to,$id]);
      }
    }
    $pdo->prepare("DELETE FROM provinces WHERE id=?")->execute([$id]);
    $pdo->commit();
    json_ok();
  }

  if ($entity === 'municipalities') {
    $refsB = 0;
    foreach (['barrios'=>'municipality_id','neighborhoods'=>'municipality_id','sectors'=>'municipality_id','sectores'=>'municipality_id'] as $t=>$c) {
      $refsB += countWhere($pdo,$t,$c,$id);
    }
    $colMun = colExists($pdo,'incidents','municipality_id') ? 'municipality_id' : (colExists($pdo,'incidents','municipio_id')?'municipio_id':null);
    $refsI  = $colMun ? countWhere($pdo,'incidents',$colMun,$id) : 0;
    $total = $refsB + $refsI;

    if ($total > 0 && !$reassign_to) {
      json_err("No se puede eliminar: tiene $refsB barrios/sectores y $refsI incidencias vinculadas.");
    }

    $pdo->beginTransaction();
    if ($reassign_to) {
      foreach (['barrios','neighborhoods','sectors','sectores'] as $t) {
        if (tableExists($pdo,$t) && colExists($pdo,$t,'municipality_id')) {
          $pdo->prepare("UPDATE `$t` SET municipality_id=? WHERE municipality_id=?")->execute([$reassign_to,$id]);
        }
      }
      if ($colMun) {
        $pdo->prepare("UPDATE incidents SET `$colMun`=? WHERE `$colMun`=?")->execute([$reassign_to,$id]);
      }
    }
    $pdo->prepare("DELETE FROM municipalities WHERE id=?")->execute([$id]);
    $pdo->commit();
    json_ok();
  }

  if ($entity === 'barrios') {
    $colBarr = colExists($pdo,'incidents','barrio_id') ? 'barrio_id' : (colExists($pdo,'incidents','neighborhood_id')?'neighborhood_id':(colExists($pdo,'incidents','sector_id')?'sector_id':null));
    $refsI   = $colBarr ? countWhere($pdo,'incidents',$colBarr,$id) : 0;

    if ($refsI > 0 && !$reassign_to) {
      json_err("No se puede eliminar: hay $refsI incidencias vinculadas.");
    }

    $pdo->beginTransaction();
    if ($reassign_to && $colBarr) {
      $pdo->prepare("UPDATE incidents SET `$colBarr`=? WHERE `$colBarr`=?")->execute([$reassign_to,$id]);
    }
    $pdo->prepare("DELETE FROM barrios WHERE id=?")->execute([$id]); // cambia si tu tabla se llama diferente
    $pdo->commit();
    json_ok();
  }

  json_err('Entidad no soportada', 400);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_err($e->getMessage(), 500);
}
