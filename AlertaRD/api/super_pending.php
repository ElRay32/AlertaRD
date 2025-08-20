<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

try {
  $inc = $pdo->query("SELECT id,title,occurrence_at FROM incidents WHERE status='pending' ORDER BY occurrence_at DESC")->fetchAll();
  $cor = $pdo->query("SELECT id,incident_id,created_at FROM incident_corrections WHERE status='pending' ORDER BY id DESC")->fetchAll();
  json_out(['incidents'=>$inc, 'corrections'=>$cor]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
