<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) json_out(['error'=>'missing id'], 400);

try {
  $c = $pdo->prepare("SELECT * FROM incident_corrections WHERE id=?");
  $c->execute([$id]);
  $corr = $c->fetch();
  if (!$corr) json_out(['error'=>'not found'], 404);

  $i = $pdo->prepare("SELECT i.*, p.name AS province, m.name AS municipality, b.name AS barrio
                      FROM incidents i
                      LEFT JOIN provinces p ON p.id=i.province_id
                      LEFT JOIN municipalities m ON m.id=i.municipality_id
                      LEFT JOIN barrios b ON b.id=i.barrio_id
                      WHERE i.id=?");
  $i->execute([$corr['incident_id']]);
  $inc = $i->fetch();

  // nombres para FK propuestos (si vienen)
  $names = ['province'=>null,'municipality'=>null,'barrio'=>null];
  if (!empty($corr['new_province_id'])) {
    $s=$pdo->prepare("SELECT name FROM provinces WHERE id=?"); $s->execute([$corr['new_province_id']]);
    $names['province'] = $s->fetchColumn();
  }
  if (!empty($corr['new_municipality_id'])) {
    $s=$pdo->prepare("SELECT name FROM municipalities WHERE id=?"); $s->execute([$corr['new_municipality_id']]);
    $names['municipality'] = $s->fetchColumn();
  }
  if (!empty($corr['new_barrio_id'])) {
    $s=$pdo->prepare("SELECT name FROM barrios WHERE id=?"); $s->execute([$corr['new_barrio_id']]);
    $names['barrio'] = $s->fetchColumn();
  }

  json_out(['correction'=>$corr, 'incident'=>$inc, 'fk_names'=>$names]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
