<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_role(['validator','admin']);

// Filtros
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');
$status    = trim($_GET['status'] ?? ''); // una sola por llamada (el frontend hace 1 llamada por estatus)

$conds=[]; $params=[];
if ($date_from!==''){ $conds[]="i.occurrence_at >= ?"; $params[]=$date_from.' 00:00:00'; }
if ($date_to  !==''){ $conds[]="i.occurrence_at < DATE_ADD(?, INTERVAL 1 DAY)"; $params[]=$date_to.' 00:00:00'; }
if ($status   !==''){ $conds[]="i.status = ?"; $params[]=$status; }
$where = $conds ? 'WHERE '.implode(' AND ',$conds) : '';

// Conteo por tipo (incidentes con tipo)
$sqlTyped = "SELECT it.name AS type_name, COUNT(*) AS total
             FROM incidents i
             JOIN incident_incident_type iit ON iit.incident_id = i.id
             JOIN incident_types it          ON it.id = iit.type_id
             $where
             GROUP BY it.id, it.name
             ORDER BY total DESC, it.name ASC";

// Conteo de incidentes sin tipo
$sqlNoType = "SELECT '(sin tipo)' AS type_name, COUNT(*) AS total
              FROM incidents i
              LEFT JOIN incident_incident_type iit ON iit.incident_id = i.id
              $where
              AND iit.incident_id IS NULL";

try{
  $st1 = $pdo->prepare($sqlTyped);  $st1->execute($params);    $rows1 = $st1->fetchAll();
  $st2 = $pdo->prepare($sqlNoType); $st2->execute($params);    $rows2 = $st2->fetchAll();

  $rows = array_merge($rows1, $rows2);
  // opcional: ordenar resultante
  usort($rows, function($a,$b){
    if ($a['total'] == $b['total']) return strcmp($a['type_name'],$b['type_name']);
    return $b['total'] <=> $a['total'];
  });

  json_out(['data'=>$rows]);
}catch(Throwable $e){
  json_out(['error'=>$e->getMessage()],500);
}
