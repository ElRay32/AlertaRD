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

<?php
// --- Session segura (llámala una sola vez por request, antes de usar $_SESSION) ---
function start_session_safe() {
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'httponly' => true,
      'samesite' => 'Lax',
      'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);
    session_start();
  }
}
start_session_safe();

// --- CSRF ---
function csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function body_json() { // si ya existe, deja el tuyo; este tolera vacío
  static $cache = null;
  if ($cache !== null) return $cache;
  $raw = file_get_contents('php://input');
  $cache = $raw ? (json_decode($raw, true) ?: []) : [];
  return $cache;
}

// Acepta token por header "X-CSRF-Token", por POST field "csrf_token" o en JSON body
function require_csrf() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') return; // solo en POST
  start_session_safe();
  $hdr = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
  $tok = $_POST['csrf_token'] ?? ($hdr ?: (body_json()['csrf_token'] ?? null));
  $sess = $_SESSION['csrf_token'] ?? '';
  if (!$tok || !$sess || !hash_equals($sess, $tok)) {
    http_response_code(419);
    echo json_encode(['error'=>'invalid CSRF']);
    exit;
  }
}
