<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

require_role(['reporter','validator','admin']);

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$province_id = (int)($_POST['province_id'] ?? 0);
$municipality_id = (int)($_POST['municipality_id'] ?? 0);
$barrio_id = (int)($_POST['barrio_id'] ?? 0);
$latitude = $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
$longitude = $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
$deaths = (int)($_POST['deaths'] ?? 0);
$injuries = (int)($_POST['injuries'] ?? 0);
$loss = $_POST['loss_estimate_rd'] !== '' ? floatval($_POST['loss_estimate_rd']) : null;
$occurrence_at = $_POST['occurrence_at'] ?? date('Y-m-d H:i:s');
$type_ids = $_POST['type_ids'] ?? []; if (!is_array($type_ids)) $type_ids = [];
$social_links = trim($_POST['social_links'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if ($title==='') json_out(['error'=>'title required'], 400);

try {
  $pdo->beginTransaction();
  $stmt = $pdo->prepare("INSERT INTO incidents (reporter_user_id,title,description,province_id,municipality_id,barrio_id,latitude,longitude,deaths,injuries,loss_estimate_rd,occurrence_at,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'pending')");
  $stmt->execute([$user_id,$title,$description ?: null,$province_id ?: null,$municipality_id ?: null,$barrio_id ?: null,$latitude,$longitude,$deaths,$injuries,$loss,$occurrence_at]);
  $incident_id = $pdo->lastInsertId();

  if ($type_ids) {
    $stmt = $pdo->prepare("INSERT INTO incident_incident_type(incident_id,type_id,is_primary) VALUES (?,?,0)");
    foreach ($type_ids as $tid) {
      $stmt->execute([$incident_id, (int)$tid]);
    }
  }

  $upload_dir = __DIR__ . '/../uploads';
  if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
  if (!empty($_FILES['photos']['name'][0])) {
    for ($i=0; $i<count($_FILES['photos']['name']); $i++) {
      $name = basename($_FILES['photos']['name'][$i]);
      $tmp = $_FILES['photos']['tmp_name'][$i];
      $target = $upload_dir . '/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $name);
      if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
        $public = '<?= $BASE_URL }}/uploads/' . basename($target);
        $stmt = $pdo->prepare("INSERT INTO incident_photos(incident_id,path_or_url,is_cover) VALUES (?,?,?)");
        $stmt->execute([$incident_id, $public, $i==0 ? 1 : 0]);
      }
    }
  }

  if ($social_links !== '') {
    $stmt = $pdo->prepare("INSERT INTO incident_social_links(incident_id,platform,url) VALUES (?,?,?)");
    foreach (preg_split('/\\r?\\n/',$social_links) as $raw) {
      $u = trim($raw); if ($u==='') continue;
      $host = parse_url($u, PHP_URL_HOST) ?: null;
      $platform = $host ? preg_replace('/^www\\.|\\.com$|\\.net$|\\.org$/','',$host) : null;
      $stmt->execute([$incident_id, $platform, $u]);
    }
  }

  $pdo->commit();
  json_out(['ok'=>true, 'id'=>$incident_id]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error'=>$e->getMessage()], 500);
}
