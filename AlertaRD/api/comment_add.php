<?php
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';
require_csrf();

require_role(['reporter','validator','admin']);
$data = $_POST + body_json();
$incident_id = (int)($data['incident_id'] ?? 0);

$content = trim($_POST['content'] ?? (body_json()['content'] ?? ''));
$content = strip_tags($content);
if (mb_strlen($content) > 1000) $content = mb_substr($content, 0, 1000);
if ($content === '') json_out(['error'=>'empty'], 422);

$user_id = $_SESSION['user_id'] ?? null;

if (!$incident_id || $content==='') json_out(['error'=>'missing data'], 400);

try {
  $stmt = $pdo->prepare("INSERT INTO incident_comments(incident_id,user_id,content) VALUES (?,?,?)");
  $stmt->execute([$incident_id, $user_id, $content]);
  json_out(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
} catch (Throwable $e) {
  json_out(['error'=>$e->getMessage()], 500);
}
