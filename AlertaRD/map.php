<?php require __DIR__.'/partials/header.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<h4 class="mb-3">Mapa de incidencias</h4>

<form class="row g-2 mb-3" id="mapFilters">
  <div class="col-sm-3">
    <input class="form-control" name="q" placeholder="Buscar por título/descr.">
  </div>
  <div class="col-sm-3">
    <select class="form-select" name="province_id" id="provinceSelect"><option value="">Provincia</option></select>
  </div>
  <div class="col-sm-2">
    <select class="form-select" name="type_id" id="typeSelect"><option value="">Tipo</option></select>
  </div>
  <div class="col-sm-2"><input type="date" class="form-control" name="date_from" id="df"></div>
  <div class="col-sm-2"><input type="date" class="form-control" name="date_to" id="dt"></div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Aplicar</button>
    <button type="button" class="btn btn-outline-secondary" id="btnClear">Limpiar</button>
    <div class="ms-auto btn-group">
      <button type="button" class="btn btn-outline-primary" data-hours="24">24h</button>
      <button type="button" class="btn btn-outline-primary" data-hours="168">7d</button>
      <button type="button" class="btn btn-outline-primary" data-hours="720">30d</button>
    </div>
  </div>
</form>

<div id="map" class="mb-3"></div>

<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Detalle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modalContent">Cargando...</div>
      </div>
    </div>
  </div>
</div>

<script>
// ------- Utilidades de color por tipo -------
const PALETTE = ['#d81b60','#1e88e5','#43a047','#f4511e','#6d4c41','#8e24aa','#3949ab','#00897b','#fb8c00','#7cb342'];
function colorForType(name) {
  let h=0; for (const ch of (name||'')) { h = (h*31 + ch.charCodeAt(0))>>>0; }
  return PALETTE[h % PALETTE.length];
}
function primaryType(typesCsv){
  if (!typesCsv) return null;
  // el primer tipo (alfabético por SQL) será el "primario" para color
  return typesCsv.split(',')[0].trim();
}

// ------- Leaflet -------
const map = L.map('map').setView([18.7357,-70.1627], 8); // RD
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution: '&copy; OpenStreetMap' }).addTo(map);
const markers = L.markerClusterGroup();
map.addLayer(markers);

// Leyenda dinámica (según tipos del catálogo)
let typesCache = [];
const Legend = L.Control.extend({
  onAdd: function() {
    const div = L.DomUtil.create('div','map-legend');
    if (!typesCache.length) { div.innerHTML = '<em>cargando tipos…</em>'; return div; }
    div.innerHTML = typesCache.map(t=>(
      `<div class="item"><span class="swatch" style="background:${colorForType(t.name)}"></span>${t.name}</div>`
    )).join('');
    return div;
  },
  onRemove:function(){}
});
const legend = new Legend({position:'bottomright'});
map.addControl(legend);

// ------- Carga de catálogos -------
async function loadCatalogs() {
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  const t = await apiGet('/alertard/api/catalogs.php?resource=types');
  typesCache = t; // para leyenda
  const ps = document.getElementById('provinceSelect');
  p.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ps.appendChild(o); });
  const ts = document.getElementById('typeSelect');
  t.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ts.appendChild(o); });

  // refresca leyenda
  map.removeControl(legend); map.addControl(legend);
}

// ------- Dibujar marcadores -------
async function loadMap(params = {}) {
  // Construye query
  const sp = new URLSearchParams(params);
  const url = '/alertard/api/incidents_map.php?' + sp.toString();
  const res = await apiGet(url);

  markers.clearLayers();
  res.data.forEach(i => {
    if (i.latitude===null || i.longitude===null) return;
    const tPrim = primaryType(i.types);
    const color = colorForType(tPrim || '');
    const icon = L.divIcon({
      className: 'pin',
      html: `<div class="pin-dot" style="background:${color}"></div>`,
      iconSize: [14,14], iconAnchor:[7,7]
    });
    const m = L.marker([i.latitude, i.longitude], {icon});
    const sub = `${i.province||''}${i.municipality?', '+i.municipality:''}`;
    m.bindPopup(`<strong>${i.title}</strong><br>${sub}<br>
      <small>${(i.types||'')}</small><br>
      <button class='btn btn-sm btn-primary mt-2' onclick='openIncident(${i.id})'>Ver detalle</button>`);
    markers.addLayer(m);
  });
}

// ------- Modal de detalle -------
async function openIncident(id) {
  const data = await apiGet('/alertard/api/incident_detail.php?id=' + id);
  document.getElementById('modalTitle').textContent = data.incident.title;
  const photos = (data.photos||[]).map(p=>`<img src="${p.path_or_url}" class="img-fluid rounded me-2 mb-2" style="max-height:120px">`).join('');
  const types = (data.types||[]).map(t=>`<span class="badge text-bg-light border me-1">${t.name}</span>`).join('');
  const links = (data.links||[]).map(l=>`<a href="${l.url}" target="_blank">${l.platform||'link'}</a>`).join(' · ');
  const comments = (data.comments||[]).map(c=>`<div class="mb-2"><strong>${c.name}</strong> <small class="text-muted">${formatDateTime(c.created_at)}</small><br>${c.content}</div>`).join('') || '<em class="text-muted">Sin comentarios</em>';

  const modalHtml = `
    <div class="row">
      <div class="col-md-8">
        <p>${data.incident.description||''}</p>
        <div class="mb-2">${types}</div>
        <div class="text-muted small mb-2">
          ${data.incident.province||''}${data.incident.municipality?', '+data.incident.municipality:''} · ${formatDateTime(data.incident.occurrence_at)}
        </div>
        <div class="mb-2">${photos}</div>
        <div class="mb-2 small">${links}</div>
      </div>
      <div class="col-md-4">
        <h6>Comentarios</h6>
        <div class="border rounded p-2 mb-2" style="max-height:220px; overflow:auto">${comments}</div>
        <form id="commentForm">
          <input type="hidden" name="incident_id" value="${data.incident.id}">
          <textarea class="form-control mb-2" name="content" placeholder="Escribe un comentario"></textarea>
          <button class="btn btn-sm btn-primary w-100" type="submit">Comentar</button>
        </form>
        <hr>
        <h6>Corrección</h6>
        <form id="corrForm">
          <input type="hidden" name="incident_id" value="${data.incident.id}">
          <div class="row g-2">
            <div class="col-6"><input class="form-control" type="number" min="0" name="new_deaths" placeholder="Muertos"></div>
            <div class="col-6"><input class="form-control" type="number" min="0" name="new_injuries" placeholder="Heridos"></div>
            <div class="col-12"><input class="form-control" type="number" step="0.000001" name="new_latitude" placeholder="Latitud"></div>
            <div class="col-12"><input class="form-control" type="number" step="0.000001" name="new_longitude" placeholder="Longitud"></div>
            <div class="col-12"><input class="form-control" type="number" step="0.01" name="new_loss_estimate_rd" placeholder="Pérdida RD$"></div>
            <div class="col-12"><input class="form-control" name="note" placeholder="Nota"></div>
          </div>
          <button class="btn btn-sm btn-outline-secondary w-100 mt-2" type="submit">Enviar corrección</button>
        </form>
      </div>
    </div>`;
  document.getElementById('modalContent').innerHTML = modalHtml;

  document.getElementById('commentForm').addEventListener('submit', async (e)=>{
    e.preventDefault(); const fd = new FormData(e.target);
    await apiPost('/alertard/api/comment_add.php', fd);
    await openIncident(id);
  });
  document.getElementById('corrForm').addEventListener('submit', async (e)=>{
    e.preventDefault(); const fd = new FormData(e.target);
    await apiPost('/alertard/api/correction_add.php', fd);
    alert('Corrección enviada para revisión.');
  });

  new bootstrap.Modal(document.getElementById('incidentModal')).show();
}

// ------- Filtros UI -------
function setDefaultLast24h() {
  const dt = new Date();
  const d2 = new Date(dt); d2.setDate(d2.getDate()-1);
  document.getElementById('df').value = d2.toISOString().slice(0,10);
  document.getElementById('dt').value = dt.toISOString().slice(0,10);
}
document.getElementById('btnClear').addEventListener('click', ()=>{
  document.getElementById('mapFilters').reset();
  // Al limpiar, mostramos 24h sin fechas (usa ?hours=24)
  loadMap({hours:24});
});

document.querySelectorAll('[data-hours]').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    document.getElementById('df').value = '';
    document.getElementById('dt').value = '';
    const hours = btn.getAttribute('data-hours');
    loadMap({hours});
  });
});

document.getElementById('mapFilters').addEventListener('submit', (e)=>{
  e.preventDefault();
  const qs = new URLSearchParams(new FormData(e.target));
  loadMap(Object.fromEntries(qs.entries()));
});

// ------- Init -------
(async function init(){
  await loadCatalogs();
  setDefaultLast24h();
  // primera carga: 24h
  await loadMap({hours:24});
})();
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
