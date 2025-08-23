<?php
// /AlertaRD/api/db.php (fixed)
declare(strict_types=1);

require_once __DIR__.'/helpers.php';

function dbx(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host    = '127.0.0.1';
  $db      = 'alertard';
  $user    = 'root';
  $pass    = '';
  $charset = 'utf8mb4';

  $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
  $opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];
  try {
    $pdo = new PDO($dsn, $user, $pass, $opts);
    $pdo->exec("SET NAMES utf8mb4");
    return $pdo;
  } catch (Throwable $e) {
    json_out(['ok'=>false,'error'=>'DB connection failed','detail'=>$e->getMessage()], 500);
  }
}

// Proveer $pdo global para los scripts antiguos
$pdo = dbx();
