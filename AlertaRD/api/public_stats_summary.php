<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

// Solo publicadas
try {
  $today = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status='published' AND DATE(occurrence_at)=CURDATE()")->fetchColumn();
  $d7    = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status='published' AND occurrence_at>=DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
  $d30   = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status='published' AND occurrence_at>=DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
  $total = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status='published'")->fetchColumn();
  json_out(['today'=>(int)$today,'last7'=>(int)$d7,'last30'=>(int)$d30,'total'=>(int)$total]);
} catch (Throwable $e){
  json_out(['error'=>$e->getMessage()], 500);
}
