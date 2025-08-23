<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');
$statuses  = $_GET['status'] ?? [];
if (!is_array($statuses)) $statuses = [$statuses];
$statuses = array_values(array_intersect($statuses, ['published','pending','rejected','deleted']));

if ($date_from === '' && $date_to === '') {
  $date_from = date('Y-m-d', strtotime('-29 days'));
  $date_to   = date('Y-m-d');
}

$conds=[]; $params=[];
if ($date_from!==''){ $conds[]="i.occurrence_at >= ?"; $params[]=$date_from.' 00:00:00'; }
if ($date_to  !==''){ $conds[]="i.occurrence_at < DATE_ADD(?, INTERVAL 1 DAY)"; $params[]=$date_to.' 00:00:00'; }
if ($statuses){ $in=implode(',', array_fill(0,count($statuses),'?')); $conds[]="i.status IN ($in)"; array_push($params, ...$statuses); }
$where = $conds ? 'WHERE '.implode(' AND ',$conds) : '';

try{
  $sql = "SELECT COALESCE(p.name,'(sin provincia)') AS province_name, COUNT(*) AS total
          FROM incidents i
          LEFT JOIN provinces p ON p.id = i.province_id
          $where
          GROUP BY p.id, p.name
          ORDER BY total DESC
          LIMIT 10";
  $st=$pdo->prepare($sql); $st->execute($params);
  json_out(['data'=>$st->fetchAll()]);
}catch(Throwable $e){ json_out(['error'=>$e->getMessage()],500); }
