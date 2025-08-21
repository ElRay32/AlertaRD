<?php
// /alertard/api/db.php
// Conexión PDO reutilizable con función dbx()

function dbx(){
  static $pdo = null;
  if ($pdo) return $pdo;

  // Ajusta aquí si tu puerto/usuario/clave son distintos
  $host = '127.0.0.1';     // en XAMPP es mejor 127.0.0.1 que "localhost"
  $db   = 'alertard';      // nombre de tu base importada
  $user = 'root';          // usuario por defecto de XAMPP
  $pass = '';              // contraseña por defecto vacía
  $charset = 'utf8mb4';

  // Si usas puerto distinto a 3306, agrega ;port=3307 (por ejemplo)
  $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

  $opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];

  try {
    $pdo = new PDO($dsn, $user, $pass, $opts);
    return $pdo;
  } catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'error'  => 'DB connection failed',
      'detail' => $e->getMessage()
    ]);
    exit;
  }
}
