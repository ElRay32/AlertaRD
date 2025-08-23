<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

$q          = trim($_GET['q'] ?? '');
$provinceId = (int)($_GET['province_id'] ?? 0);
$typeId     = (int)($_GET['type_id'] ?? 0);
$dateFrom   = trim($_GET['date_from'] ?? '');
$dateTo     = trim($_GET['date_to'] ?? '');
$limit      = min(2000, max(1, (int)($_GET['limit'] ?? 1000)));

$conds = ["i.status='published'", "i.latitude IS NOT NULL", "i.longitude IS NOT NULL"];
$params = [];

if ($q !== '') { $conds[]="(i.title LIKE ? OR i.description LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
if ($provinceId>0){ $conds[]="i.province_id=?"; $params[]=$provinceId; }
if ($typeId>0){ $conds[]="EXISTS (SELECT 1 FROM incident_incident_type x WHERE x.incident_id=i.id AND x.type_id=?)"; $params[]=$typeId; }
if ($dateFrom!==''){ $conds[]="i.occurrence_at>=?"; $params[]=$dateFrom.' 00:00:00'; }
if ($dateTo  !==''){ $conds[]="i.occurrence_at<DATE_ADD(?, INTERVAL 1 DAY)"; $params[]=$dateTo.' 00:00:00'; }
$whereSql = 'WHERE '.implode(' AND ', $conds);

$sql = "SELECT
          i.id, i.title, i.occurrence_at, i.latitude, i.longitude,
          p.name AS province, m.name AS municipality,
          (SELECT GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ')
             FROM incident_incident_type iit
             JOIN incident_types it ON it.id = iit.type_id
             WHERE iit.incident_id = i.id) AS types
        FROM incidents i
        LEFT JOIN provinces p ON p.id=i.province_id
        LEFT JOIN municipalities m ON m.id=i.municipality_id
        $whereSql
        ORDER BY i.occurrence_at DESC
        LIMIT {$limit}";
try {
  $st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
  json_out(['data'=>$rows]);
} catch (Throwable $e){
  json_out(['error'=>$e->getMessage()], 500);
}
