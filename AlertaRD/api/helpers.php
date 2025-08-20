<?php
function json_out($data, $code=200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function require_role($roles) {
  if (!is_array($roles)) { $roles = [$roles]; }
  if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
    json_out(['error'=>'unauthorized'], 401);
  }
}
function param($name, $default=null) {
  return $_GET[$name] ?? $_POST[$name] ?? $default;
}
function body_json() {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
