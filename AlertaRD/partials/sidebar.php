<?php $role = $_SESSION['role'] ?? 'guest'; ?>
<nav class="nav-vertical">
  <small class="mb-1">Público</small>
  <a data-route="index.php" href="/alertard/index.php">🏠 Inicio / Mapa</a>
  <a data-route="incidents.php" href="/alertard/incidents.php">📋 Incidencias</a>
  <a data-route="report.php" href="/alertard/report.php">📝 Reportar</a>

  <?php if (in_array($role, ['validator','admin'])): ?>
    <small class="mt-3 mb-1">Validación</small>
    <a data-route="super/reports.php" href="/alertard/super/reports.php">✅ Pendientes</a>
    <a data-route="super/stats.php" href="/alertard/super/stats.php">📊 Estadísticas</a>
    <a data-route="super/catalogs.php" href="/alertard/super/catalogs.php">🗂️ Catálogos</a>
  <?php endif; ?>

  <?php if ($role === 'admin'): ?>
    <small class="mt-3 mb-1">Admin</small>
    <a data-route="super/export.php" href="/alertard/super/export.php">⬇️ Exportar</a>
  <?php endif; ?>
</nav>
