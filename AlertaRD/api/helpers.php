<?php
declare(strict_types=1);

/** Respuestas JSON + status */
if (!function_exists('json_out')) {
  function json_out($data, int $code=200): void {
    if (!headers_sent()) {
      http_response_code($code);
      header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/** Sesión segura + token CSRF */
if (!function_exists('start_session_safe')) {
  function start_session_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
      if (!ini_get('session.save_path')) @ini_set('session.save_path', sys_get_temp_dir());
      @session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
  }
}

/** Lee body JSON si aplica */
if (!function_exists('body_json')) {
  function body_json(): array {
    $ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    if (strpos($ct, 'application/json') === 0) {
      $raw = file_get_contents('php://input') ?: '';
      if ($raw !== '') {
        try { return (array)json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { return []; }
      }
    }
    return [];
  }
}

/** Exige rol */
if (!function_exists('require_role')) {
  function require_role(array $allowed): void {
    start_session_safe();
    $role = $_SESSION['role'] ?? 'guest';
    if (!in_array($role, $allowed, true)) {
      json_out(['ok'=>false,'error'=>'forbidden','role'=>$role], 403);
    }
  }
}

/** Verifica CSRF (header, POST o JSON) */
if (!function_exists('require_csrf')) {
  function require_csrf(): void {
    start_session_safe();
    $hdr  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $jb   = body_json();
    $tok  = $_POST['csrf_token'] ?? $_POST['csrf'] ?? ($hdr ?: ($jb['csrf_token'] ?? $jb['csrf'] ?? null));
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!$tok || !$sess || !hash_equals($sess, $tok)) {
      json_out(['ok'=>false,'error'=>'invalid CSRF'], 419);
    }
  }
}

/** Devuelve token CSRF actual */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    start_session_safe();
    return (string)($_SESSION['csrf_token'] ?? '');
  }
}

/** ID de usuario logueado (opcional) */
if (!function_exists('user_id')) {
  function user_id(): ?int {
    start_session_safe();
    $uid = $_SESSION['user_id'] ?? null;
    return $uid ? (int)$uid : null;
  }
}
