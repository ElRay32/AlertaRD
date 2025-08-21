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
        <a class="nav-link" href="/alertard/incidents.php">Incidencias</a>
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
