<?php $role = $_SESSION['role'] ?? 'guest'; ?>
<nav class="nav-vertical">
  <small class="mb-1">Público</small>
  <a data-route="index.php" href="<?= $BASE_URL ?>/index.php">🏠 Inicio / Mapa</a>
  <a data-route="incidents.php" href="<?= $BASE_URL ?>/incidents.php">📋 Incidencias</a>
  <a data-route="report.php" href="<?= $BASE_URL ?>/report.php">📝 Reportar</a>

  <?php if (in_array($role, ['validator','admin'])): ?>
    <small class="mt-3 mb-1">Validación</small>
    <!-- <a data-route="super/reports.php" href="<?= $BASE_URL ?>/super/dashboard.php">🤖 Dashboard</a> -->
    <a data-route="super/reports.php" href="<?= $BASE_URL ?>/super/reports.php">✅ Pendientes</a>
    <a href="<?= $BASE_URL ?>/super/rejected.php" class="list-group-item list-group-item-action">❌Rechazados</a>
    <a data-route="super/stats.php" href="<?= $BASE_URL ?>/super/stats.php">📊 Estadísticas</a>
    <a data-route="super/catalogs.php" href="<?= $BASE_URL ?>/super/catalogs.php">🗂️ Catálogos</a>
  <?php endif; ?>

  <?php if ($role === 'admin'): ?>
  <!--   <small class="mt-3 mb-1">Admin</small>
   <a data-route="super/export.php" href="<?= $BASE_URL ?>/super/export.php">⬇️ Exportar</a> -->
  <?php endif; ?>
</nav>
