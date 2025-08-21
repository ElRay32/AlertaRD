<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$role = $_SESSION['role'] ?? 'guest';
$displayName = $_SESSION['name'] ?? 'Invitado';
?>

<?php
// Cabeceras seguras mínimas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>


<!doctype html>
<html lang="es">
<head>

<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<script>
// Helpers fetch básicos (si ya tienes apiGet/apiPost, actualízalos con el header)
window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function apiGet(url){
  const res = await fetch(url, {credentials:'same-origin'});
  return await res.json();
}
async function apiPost(url, data){ // data: FormData o objeto
  let body, headers = {'X-CSRF-Token': window.CSRF_TOKEN};
  if (data instanceof FormData) { body = data; }
  else if (data instanceof URLSearchParams) { body = data; }
  else { headers['Content-Type']='application/json'; body = JSON.stringify(data); }
  const res = await fetch(url, {method:'POST', body, headers, credentials:'same-origin'});
  return await res.json();
}
</script>


  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AlertarRD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/alertard/assets/css/styles.css" rel="stylesheet">
      <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <!-- GLightbox  -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

  <!-- Theme -->
  <link rel="icon" href="/alertard/assets/img/favicon.svg">
  <link rel="stylesheet" href="/alertard/assets/css/theme.css">
</head>
<body>

<header class="app-header">
  <div class="brand">
    <a href="/alertard/index.php" class="d-inline-flex align-items-center text-decoration-none">
      <img src="/alertard/assets/img/favicon.svg" alt="Logo" width="28" height="28" class="me-2">
      <h1 class="m-0">AlertARD <span class="badge">v1</span></h1>
    </a>
  </div>
  <nav class="top-actions">
    <button id="btnTheme" class="btn btn-sm btn-theme" title="Cambiar tema">🌓</button>
    <?php if ($role !== 'guest'): ?>
      <span class="text-muted small me-2">Hola, <?= htmlspecialchars($user_name) ?></span>
      <a class="btn btn-sm btn-outline-danger" href="/alertard/auth/logout.php">Salir</a>
    <?php else: ?>
      <a class="btn btn-sm btn-outline-primary" href="/alertard/auth/login.php">Entrar</a>
    <?php endif; ?>
  </nav>
</header>

<!-- Shell: sidebar + contenido -->
<div class="container-fluid">
  <div class="row">
    <aside class="col-lg-3 col-xl-2 d-none d-lg-block sidebar">
      <?php require __DIR__.'/sidebar.php'; ?>
    </aside>
    <main class="col-12 col-lg-9 col-xl-10 app-content">
