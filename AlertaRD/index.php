<?php require __DIR__.'/partials/header.php'; ?>
<h4 class="mb-3">Incidencias</h4>
<form class="row g-2 mb-3" id="filters">
  <div class="col-sm-3"><input class="form-control" name="q" placeholder="Buscar por título o descripción"></div>
  <div class="col-sm-3">
    <select class="form-select" name="province_id" id="provinceSelect"><option value="">Provincia</option></select>
  </div>
  <div class="col-sm-2">
    <select class="form-select" name="type_id" id="typeSelect"><option value="">Tipo</option></select>
  </div>
  <div class="col-sm-2"><input type="date" class="form-control" name="date_from"></div>
  <div class="col-sm-2"><input type="date" class="form-control" name="date_to"></div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Aplicar</button>
    <button class="btn btn-outline-secondary" type="button" id="clearBtn">Limpiar</button>
    <a class="btn btn-outline-primary ms-auto" href="/alertard/map.php">Ver Mapa</a>
  </div>
</form>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead><tr><th>Fecha</th><th>Título</th><th>Ubicación</th><th>Tipos</th><th>Estado</th></tr></thead>
  <tbody id="rows"></tbody>
</table>
</div>

<script>
async function loadCatalogs() {
  let p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  let t = await apiGet('/alertard/api/catalogs.php?resource=types');
  const ps = document.getElementById('provinceSelect');
  p.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ps.appendChild(o); });
  const ts = document.getElementById('typeSelect');
  t.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ts.appendChild(o); });
}
async function loadList() {
  const params = new URLSearchParams(new FormData(document.getElementById('filters')));
  const res = await apiGet('/alertard/api/incidents_list.php?' + params.toString());
  const tb = document.getElementById('rows'); tb.innerHTML='';
  res.data.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${formatDateTime(r.occurrence_at)}</td>
      <td><a href="/alertard/incident.php?id=${r.id}">${r.title}</a><br><small class="text-muted">${r.excerpt||''}</small></td>
      <td>${r.province||''}${r.municipality?', '+r.municipality:''}</td>
      <td>${(r.types||'').split(',').map(x=>`<span class="badge text-bg-light border badge-type">${x.trim()}</span>`).join(' ')}</td>
      <td><span class="badge text-bg-${r.status==='published'?'success':(r.status==='pending'?'warning':'secondary')}">${r.status}</span></td>`;
    tb.appendChild(tr);
  });
}
document.getElementById('filters').addEventListener('submit', (e)=>{e.preventDefault(); loadList();});
document.getElementById('clearBtn').addEventListener('click', ()=>{ document.getElementById('filters').reset(); loadList(); });
loadCatalogs().then(loadList);
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
