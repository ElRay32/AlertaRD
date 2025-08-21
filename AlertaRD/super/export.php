<?php require __DIR__.'/../partials/header.php'; ?>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<h4 class="mb-3">Exportar incidencias a CSV</h4>

<form id="expForm" class="row g-2" method="get" action="/alertard/api/export_csv.php" target="_blank">
  <div class="col-md-3">
    <input class="form-control" name="q" placeholder="Buscar por título/descr.">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="province_id" id="provinceSelect">
      <option value="">Provincia</option>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="type_id" id="typeSelect">
      <option value="">Tipo</option>
    </select>
  </div>
  <div class="col-md-2"><input type="date" class="form-control" name="date_from"></div>
  <div class="col-md-2"><input type="date" class="form-control" name="date_to"></div>

  <div class="col-md-2">
    <select class="form-select" name="status">
      <option value="">Estado (todos)</option>
      <option value="published">Publicadas</option>
      <option value="pending">Pendientes</option>
      <option value="merged">Unidas (merged)</option>
      <option value="rejected">Rechazadas</option>
      <option value="applied">Aplicadas</option>
    </select>
  </div>
  <div class="col-md-3">
    <select class="form-select" name="has_coords">
      <option value="-1">Coordenadas (todas)</option>
      <option value="1">Solo con lat/lng</option>
      <option value="0">Solo sin lat/lng</option>
    </select>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Exportar CSV</button>
    <button class="btn btn-outline-secondary" type="button" id="btnPreview">Previsualizar conteo</button>
  </div>
</form>

<div id="preview" class="mt-3"></div>

<script>
async function loadCatalogs() {
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  const t = await apiGet('/alertard/api/catalogs.php?resource=types');
  const ps = document.getElementById('provinceSelect');
  const ts = document.getElementById('typeSelect');
  p.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ps.appendChild(o); });
  t.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ts.appendChild(o); });
}
// Previsualización rápida: usa la API de lista (muestra total y primeras 10 filas)
document.getElementById('btnPreview').addEventListener('click', async ()=>{
  const qs = new URLSearchParams(new FormData(document.getElementById('expForm')));
  // reusa incidents_list (sin límite fuerte; pedimos 10)
  qs.set('limit','10'); qs.set('page','1');
  const res = await apiGet('/alertard/api/incidents_list.php?' + qs.toString());
  const el = document.getElementById('preview');
  if (!res.data.length) { el.innerHTML = '<div class="alert alert-warning">No hay resultados para los filtros seleccionados.</div>'; return; }
  const rows = res.data.map(r=>`
    <tr>
      <td>${r.id||''}</td>
      <td>${r.title||''}</td>
      <td>${r.province||''}${r.municipality?', '+r.municipality:''}</td>
      <td>${r.types||''}</td>
      <td>${formatDateTime(r.occurrence_at)||''}</td>
    </tr>`).join('');
  el.innerHTML = `
    <div class="alert alert-info">Se mostrarán las primeras 10 incidencias encontradas. El CSV incluirá <strong>todas</strong> las que cumplan los filtros.</div>
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>ID</th><th>Título</th><th>Ubicación</th><th>Tipos</th><th>Fecha</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
});
loadCatalogs();
</script>

<?php require __DIR__.'/../partials/footer.php'; ?>
