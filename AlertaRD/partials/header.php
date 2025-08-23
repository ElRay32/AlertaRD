<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../api/helpers.php';

$doc = str_replace('\\','/', realpath($_SERVER['DOCUMENT_ROOT']));
$app = str_replace('\\','/', realpath(__DIR__.'/..'));
$BASE_URL = rtrim(str_replace($doc, '', $app), '/');
if ($BASE_URL === '/') { $BASE_URL = ''; }

$role = $_SESSION['role'] ?? 'guest';
$displayName = $_SESSION['name'] ?? 'Invitado';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <title><?= isset($title) ? htmlspecialchars($title) : 'AlertARD' ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE_URL ?>/assets/img/favicon.svg">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/theme.css">
  <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/theme-light-override.css">
</head>
<body>
<header class="app-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
  <a class="d-inline-flex align-items-center text-decoration-none" href="<?= $BASE_URL ?>/index.php">
    <img src="<?= $BASE_URL ?>/assets/img/favicon.svg" width="28" height="28" class="me-2" alt="Logo">
    <strong>AlertaRD</strong> <span class="badge bg-secondary ms-2">v1</span>
  </a>
  <nav class="top-actions d-flex align-items-center gap-2">
    <button id="btnSidebar" class="btn btn-sm btn-outline-secondary" title="Ocultar/mostrar menú">☰</button>
    <?php if ($role !== 'guest'): ?>
      <span class="text-muted small">Hola, <?= htmlspecialchars($displayName) ?></span>
      <a class="btn btn-sm btn-outline-danger" href="<?= $BASE_URL ?>/auth/logout.php">Salir</a>
    <?php else: ?>
      <a class="btn btn-sm btn-primary" href="<?= $BASE_URL ?>/auth/login.php">Entrar</a>
    <?php endif; ?>
  </nav>
</header>

<div class="container-fluid">
  <div class="row">
    <aside class="col-lg-3 col-xl-2 d-none d-lg-block sidebar">
      <?php require __DIR__.'/sidebar.php'; ?>
    </aside>
    <main class="col-12 col-lg-9 col-xl-10 app-content">
