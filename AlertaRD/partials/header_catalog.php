<?php
// /AlertaRD/partials/header_catalog.php  (Header minimal sólo para Catálogos)
start_session_safe();
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$BASE_SIMPLE = preg_replace('#/super/[^/]+$#', '', $script);
if (!$BASE_SIMPLE) $BASE_SIMPLE = '/';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($title) ? htmlspecialchars($title) : 'Catálogos' ?> · AlertaRD</title>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE_URL ?>/assets/img/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f7f7f9}
    .brand-dot{display:inline-block;width:12px;height:12px;border-radius:50%;background:#dc3545;margin-right:8px;vertical-align:baseline}
    .nav-link.active{font-weight:600}
  </style>
</head>
<body>
<script>window.BASE = <?= json_encode($BASE_SIMPLE, JSON_UNESCAPED_SLASHES) ?>;</script>

<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= $BASE_SIMPLE ?>/index.php">
      <span class="brand-dot"></span>
      <strong>AlertaRD</strong><span class="text-muted ms-2">/ <?= isset($title) ? htmlspecialchars($title) : 'Catálogos' ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= $BASE_SIMPLE ?>/index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $BASE_SIMPLE ?>/incidents.php">Incidencias</a></li>
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= $BASE_SIMPLE ?>/super/reports.php">Reportes</a></li>
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= $BASE_SIMPLE ?>/super/stats.php">Estadísticas</a></li>
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= $BASE_SIMPLE ?>/super/catalogs.php">Catálogos</a></li>
      </ul>
      <div class="d-flex">
        <a class="btn btn-outline-danger btn-sm" href="<?= $BASE_SIMPLE ?>/logout.php">Salir</a>
      </div>
    </div>
  </div>
</nav>
