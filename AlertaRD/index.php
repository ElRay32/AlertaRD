<?php $title='Inicio / Mapa'; require __DIR__.'/partials/header.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="mb-0">Incidencias</h4>
    <a class="btn btn-outline-primary" href="<?= $BASE_URL ?>/report.php">Reportar</a>
  </div>

  <!-- Filtros -->
  <form id="filters" class="row g-2 mb-3">
    <div class="col-md-4"><input class="form-control" name="q" placeholder="Buscar por título o descripción"></div>
    <div class="col-md-3">
      <select class="form-select" name="province_id" id="provinceSelect"><option value="">Provincia</option></select>
    </div>
    <div class="col-md-2">
      <select class="form-select" name="type_id" id="typeSelect"><option value="">Tipo</option></select>
    </div>
    <div class="col-md-1"><input type="date" class="form-control" name="date_from"></div>
    <div class="col-md-1"><input type="date" class="form-control" name="date_to"></div>
    <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit">Aplicar</button></div>
    <div class="col-md-1 d-grid"><button class="btn btn-outline-secondary" type="button" id="btnClear">Limpiar</button></div>
  </form>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Hoy</div><div class="fs-3" id="kpiToday">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Últimos 7 días</div><div class="fs-3" id="kpi7">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Últimos 30 días</div><div class="fs-3" id="kpi30">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Total publicadas</div><div class="fs-3" id="kpiTotal">—</div></div></div></div>
  </div>

  <!-- Mapa -->
  <div id="map" class="rounded border" style="height: 480px;"></div>

  <!-- Lista corta -->
  <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
    <h6 class="mb-0">Últimas incidencias</h6>
    <a id="lnkVerMas" class="btn btn-sm btn-outline-primary">Ver todas</a>
  </div>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead>
        <tr><th>Fecha</th><th>Título</th><th>Ubicación</th><th>Tipos</th><th></th></tr>
      </thead>
      <tbody id="tblBody"><tr><td colspan="5">Cargando…</td></tr></tbody>
    </table>
  </div>
</div>

<script>
let map, markers;

function qsFromForm(){
  const fd = new FormData(document.getElementById('filters'));
  const qs = new URLSearchParams();
  for (const [k,v] of fd.entries()) if (String(v).trim()!=='') qs.set(k, v);
  return qs;
}
function option(el, value, text){ const o=document.createElement('option'); o.value=value; o.textContent=text; el.appendChild(o); }

async function loadFilters(){
  // Provincias
  const selProv = document.getElementById('provinceSelect');
  selProv.innerHTML=''; option(selProv,'','Provincia');
  const rp = await apiGet('/api/catalog_list.php?entity=provinces'); (rp.data||[]).forEach(p=>option(selProv,p.id,p.name));
  // Tipos
  const selType = document.getElementById('typeSelect');
  selType.innerHTML=''; option(selType,'','Tipo');
  const rt = await apiGet('/api/catalog_list.php?entity=types'); (rt.data||[]).forEach(t=>option(selType,t.id,t.name));
}

function fmtDate(s){ return String(s||'').replace('T',' ').slice(0,16); }
function esc(s){ return (s||'').replace(/[&<>"]/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c])); }

function ensureMap(){
  if (map) return map;
  map = L.map('map').setView([18.7357,-70.1627], 8);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
  markers = L.markerClusterGroup();
  map.addLayer(markers);
  setTimeout(()=> map.invalidateSize(), 0);
  return map;
}

async function loadMap(){
  ensureMap(); markers.clearLayers();
  const qs = qsFromForm();
  const res = await apiGet('/api/incidents_map.php?' + qs.toString());
  (res.data||[]).forEach(r=>{
    if (r.latitude==null || r.longitude==null) return;
    const m = L.marker([Number(r.latitude), Number(r.longitude)]);
    const loc = [r.province, r.municipality].filter(Boolean).join(', ');
    const link = (window.BASE_URL||'') + '/incident.php?id=' + r.id;
    m.bindPopup(`<b>${esc(r.title||'(sin título)')}</b><br>${esc(loc)}<br>${fmtDate(r.occurrence_at)}<br><small>${esc(r.types||'(sin tipo)')}</small><br><a href="${link}">Ver detalle</a>`);
    markers.addLayer(m);
  });
}

async function loadKPIs(){
  const k = await apiGet('/api/public_stats_summary.php');
  document.getElementById('kpiToday').textContent = k.today ?? 0;
  document.getElementById('kpi7').textContent    = k.last7 ?? 0;
  document.getElementById('kpi30').textContent   = k.last30 ?? 0;
  document.getElementById('kpiTotal').textContent= k.total ?? 0;
}

async function loadList(){
  const qs = qsFromForm(); qs.set('page', 1); qs.set('limit', 10); qs.set('status','published');
  const res = await apiGet('/api/incidents_list.php?' + qs.toString());
  const tb  = document.getElementById('tblBody');
  tb.innerHTML = (res.data||[]).map(r=>{
    const loc = [r.province, r.municipality].filter(Boolean).join(', ');
    const link = (window.BASE_URL||'') + '/incident.php?id=' + r.id;
    return `<tr>
      <td>${fmtDate(r.occurrence_at)}</td>
      <td>${esc(r.title||'(sin título)')}</td>
      <td>${esc(loc||'(sin ubicación)')}</td>
      <td>${esc(r.types||'(sin tipo)')}</td>
      <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="${link}">Ver</a></td>
    </tr>`;
  }).join('') || `<tr><td colspan="5" class="text-muted">No hay incidencias publicadas con esos filtros.</td></tr>`;

  // link "ver todas" hacia incidents.php con el mismo filtro
  document.getElementById('lnkVerMas').href = (window.BASE_URL||'') + '/incidents.php?' + qsFromForm().toString();
}

document.addEventListener('DOMContentLoaded', async ()=>{
  try { await loadFilters(); } catch(e){ console.warn('Catálogos no disponibles (continuo)'); }
  await Promise.all([loadKPIs(), loadMap(), loadList()]);
  document.getElementById('filters').addEventListener('submit', (e)=>{ e.preventDefault(); loadMap(); loadList(); });
  document.getElementById('btnClear').addEventListener('click', ()=>{ document.getElementById('filters').reset(); loadMap(); loadList(); });
});

</script>

<?php require __DIR__.'/partials/footer.php'; ?>
