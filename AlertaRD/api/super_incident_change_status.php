<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

require_csrf();                      // si te da 403 mientras pruebas, coméntalo temporalmente
require_role(['validator','admin']); // quiénes pueden cambiar/borrar

$id     = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$hard   = (int)($_POST['hard'] ?? 0); // 1 = borrar definitivamente

$allowed = ['pending','rejected','published','deleted'];
if (!$id) { json_out(['ok'=>false,'error'=>'missing id'], 400); }

// helpers
function columnExists(PDO $pdo, string $table, string $col): bool {
  $st = $pdo->prepare("SELECT 1 FROM information_schema.columns
                       WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
  $st->execute([$table, $col]);
  return (bool)$st->fetchColumn();
}
function tableExists(PDO $pdo, string $name): bool {
  $st = $pdo->prepare("SELECT 1 FROM information_schema.tables
                       WHERE table_schema = DATABASE() AND table_name = ?");
  $st->execute([$name]);
  return (bool)$st->fetchColumn();
}

try {
  if ($hard === 1) {
    // ======= HARD DELETE =======
    $pdo->beginTransaction();

    // elimina archivos locales de fotos (si usas paths tipo /uploads/...)
    if (tableExists($pdo, 'incident_photos')) {
      $st = $pdo->prepare("SELECT path_or_url FROM incident_photos WHERE incident_id=?");
      $st->execute([$id]);
      foreach ($st->fetchAll() as $row) {
        $p = $row['path_or_url'];
        if ($p && !preg_match('#^https?://#i', $p) && str_starts_with($p, '/uploads/')) {
          $abs = realpath(__DIR__.'/..'.$p); if ($abs && is_file($abs)) @unlink($abs);
        }
      }
    }

    // borra relaciones por incident_id (solo si existen)
    foreach (['incident_photos','incident_incident_type','incident_comments','incident_history'] as $tbl) {
      if (tableExists($pdo, $tbl)) {
        $pdo->prepare("DELETE FROM `$tbl` WHERE incident_id=?")->execute([$id]);
      }
    }

    // borra la fila principal
    $del = $pdo->prepare("DELETE FROM incidents WHERE id=?");
    $del->execute([$id]);

    $pdo->commit();
    json_out(['ok'=>true,'hard'=>1,'deleted'=>$del->rowCount()]);
  }

  // ======= SOFT (cambio de estado) =======
  if (!in_array($status, $allowed, true)) {
    json_out(['ok'=>false,'error'=>'bad params'], 400);
  }

  $hasUpdatedAt   = columnExists($pdo, 'incidents', 'updated_at');
  $hasPublishedAt = columnExists($pdo, 'incidents', 'published_at');

  $sql = "UPDATE incidents SET status=?";
  $params = [$status];
  if ($hasUpdatedAt) $sql .= ", updated_at=NOW()";
  if ($status === 'published' && $hasPublishedAt) $sql .= ", published_at=NOW()";
  $sql .= " WHERE id=?";
  $params[] = $id;

  $st = $pdo->prepare($sql);
  $st->execute($params);

  json_out(['ok'=>true,'hard'=>0,'affected'=>$st->rowCount()]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
