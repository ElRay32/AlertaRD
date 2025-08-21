<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$role = $_SESSION['role'] ?? 'guest';
$displayName = $_SESSION['name'] ?? 'Invitado';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AlertarRD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/alertard/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/alertard/index.php">AlertarRD</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/alertard/map.php">Mapa 24h</a></li>
        <li class="nav-item"><a class="nav-link" href="/alertard/report.php">Reportar</a></li>
        <?php if ($role === 'validator' || $role === 'admin'): ?>
        <li class="nav-item"><a class="nav-link" href="/alertard/super/dashboard.php">/super</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex">
        <?php if ($role === 'guest'): ?>
  <a class="btn btn-outline-primary me-2" href="/alertard/auth/login.php">Entrar</a>
<?php else: ?>
          <span class="me-3 small text-muted">Sesión: <strong><?php echo htmlspecialchars($displayName) ?></strong> (<?php echo htmlspecialchars($role) ?>)</span>
          <a class="btn btn-outline-danger" href="/alertard/auth/logout.php">Salir</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<div class="container py-3">
