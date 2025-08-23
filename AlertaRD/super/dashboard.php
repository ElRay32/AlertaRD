<?php
// super/dashboard.php
require __DIR__ . '/../api/helpers.php';
start_session_safe();

$role  = $_SESSION['role'] ?? 'guest';
$title = 'Panel (Pendientes)';

require __DIR__ . '/../partials/header.php';
?>
<h4 class="mb-3">/super - Pendientes</h4>

<div class="mb-3">
  <a class="btn btn-primary" href="<?= $BASE_URL ?>/super/reports.php">Validar reportes</a>
  <a class="btn btn-outline-primary" href="<?= $BASE_URL ?>/super/catalogs.php">Administrar Catálogos</a>
  <a class="btn btn-outline-secondary" href="<?= $BASE_URL ?>/super/stats.php">Ver estadísticas</a>
  <a class="btn btn-outline-dark" href="<?= $BASE_URL ?>/super/import.php">Importar CSV</a>
  <a class="btn btn-success" href="<?= $BASE_URL ?>/super/export.php">Exportar CSV</a>
</div>

<?php if ($role !== 'validator' && $role !== 'admin'): ?>
  <div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
  <?php require __DIR__ . '/../partials/footer.php'; exit; ?>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Incidentes pendientes</div>
      <ul class="list-group list-group-flush" id="pendInc"></ul>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Correcciones pendientes</span>
        <button class="btn btn-sm btn-outline-primary" onclick="reloadCorrections()">Recargar</button>
      </div>
      <ul class="list-group list-group-flush" id="pendCorr"></ul>
    </div>
  </div>
</div>

<!-- Modal para revisar corrección -->
<div class="modal fade" id="corrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Revisión de corrección</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="corrBody">Cargando...</div>
      <div class="modal-footer">
        <button class="btn btn-outline-danger" id="btnReject">Rechazar</button>
        <button class="btn btn-success" id="btnApply">Aplicar</button>
      </div>
    </div>
  </div>
</div>

<script>
async function reloadCorrections() {
  const res = await apiGet('<?= $BASE_URL ?>/api/super_corrections_list.php');
  const ul = document.getElementById('pendCorr'); ul.innerHTML = '';
  if (!res.data || !res.data.length) {
    ul.innerHTML = '<li class="list-group-item text-muted">No hay correcciones pendientes</li>';
    return;
  }
  res.data.forEach(c => {
    const tags = [
      c.f_deaths ? 'Muertos' : null,
      c.f_injuries ? 'Heridos' : null,
      c.f_loss ? 'Pérdida' : null,
      (c.f_lat || c.f_lng) ? 'Coords' : null,
      c.f_province ? 'Provincia' : null,
      c.f_muni ? 'Municipio' : null,
      c.f_barrio ? 'Barrio' : null
    ].filter(Boolean).join(', ');

    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `
      <span>Corrección #${c.id} · Incidente ${c.incident_id}
        <small class="text-muted">(${tags || '—'}) · ${formatDateTime(c.created_at)}</small>
      </span>
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-secondary" onclick="openCorr(${c.id})">Revisar</button>
      </div>`;
    ul.appendChild(li);
  });
}

let currentCorrId = null;
async function openCorr(id) {
  currentCorrId = id;
  const data = await apiGet('<?= $BASE_URL ?>/api/super_correction_detail.php?id=' + id);
  const i = data.incident, c = data.correction, fk = data.fk_names || {};

  function row(label, actual, proposed) {
    const a = (actual !== null && actual !== undefined) ? actual : '—';
    const p = (proposed !== null && proposed !== undefined) ? proposed : '—';
    const changed = (proposed !== null && proposed !== undefined);
    return `
    <tr>
      <th>${label}</th>
      <td>${a}</td>
      <td>${p}${changed ? ' <span class="badge text-bg-warning">propuesto</span>' : ''}</td>
    </tr>`;
  }

  const html = `
    <div class="mb-2"><strong>Incidente #${i.id}</strong> · ${i.title}</div>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Campo</th><th>Actual</th><th>Propuesto</th></tr></thead>
        <tbody>
          ${row('Muertos', i.deaths, c.new_deaths)}
          ${row('Heridos', i.injuries, c.new_injuries)}
          ${row('Pérdida RD$', i.loss_estimate_rd, c.new_loss_estimate_rd)}
          ${row('Latitud', i.latitude, c.new_latitude)}
          ${row('Longitud', i.longitude, c.new_longitude)}
          ${row('Provincia', i.province, fk.province)}
          ${row('Municipio', i.municipality, fk.municipality)}
          ${row('Barrio', i.barrio, fk.barrio)}
        </tbody>
      </table>
    </div>
    ${c.note ? `<div class="small text-muted">Nota del usuario: ${c.note}</div>` : ''}`;
  document.getElementById('corrBody').innerHTML = html;

  const modal = new bootstrap.Modal(document.getElementById('corrModal'));
  modal.show();

  document.getElementById('btnApply').onclick = async () => {
    await apiPost('<?= $BASE_URL ?>/api/super_correction_apply.php', { id: currentCorrId });
    modal.hide();
    await reloadCorrections();
  };
  document.getElementById('btnReject').onclick = async () => {
    await apiPost('<?= $BASE_URL ?>/api/super_correction_reject.php', { id: currentCorrId });
    modal.hide();
    await reloadCorrections();
  };
}

// cargar al entrar
reloadCorrections();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
