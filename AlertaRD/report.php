<?php require __DIR__.'/partials/header.php'; ?>
<?php if (!in_array(($role ?? 'guest'), ['reporter','validator','admin'])): ?>
<div class="alert alert-warning">Debes iniciar sesión para reportar.</div>
<?php require __DIR__.'/partials/footer.php'; exit; endif; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<h4 class="mb-3">Reportar incidencia</h4>

<form id="repForm" class="row g-3" enctype="multipart/form-data">
  <div class="col-md-8">
    <label class="form-label">Título *</label>
    <input class="form-control" name="title" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Fecha y hora *</label>
    <input type="datetime-local" class="form-control" name="occurrence_at" required>
  </div>

  <div class="col-12">
    <label class="form-label">Descripción</label>
    <textarea class="form-control" name="description" rows="4"></textarea>
  </div>

  <div class="col-md-4">
    <label class="form-label">Provincia</label>
    <select class="form-select" name="province_id" id="provinceSelect">
      <option value="">(Seleccione)</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Municipio</label>
    <select class="form-select" name="municipality_id" id="municipalitySelect" disabled>
      <option value="">(Seleccione)</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Barrio</label>
    <select class="form-select" name="barrio_id" id="barrioSelect" disabled>
      <option value="">(Seleccione)</option>
    </select>
  </div>

  <div class="col-12">
    <label class="form-label d-block">Tipo(s) de incidencia *</label>
    <div id="typesWrap"></div>
    <div class="form-text">Selecciona uno o varios.</div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Muertos</label>
    <input type="number" min="0" class="form-control" name="deaths">
  </div>
  <div class="col-md-6">
    <label class="form-label">Heridos</label>
    <input type="number" min="0" class="form-control" name="injuries">
  </div>
  <div class="col-md-6">
    <label class="form-label">Pérdida estimada (RD$)</label>
    <input type="number" step="0.01" min="0" class="form-control" name="loss_estimate_rd">
  </div>

  <div class="col-md-6">
    <label class="form-label">Enlaces a redes (uno por línea)</label>
    <textarea class="form-control" name="social_links" rows="3" placeholder="https://twitter.com/...\nhttps://www.instagram.com/p/..."></textarea>
  </div>

  <div class="col-12">
    <label class="form-label d-block">Ubicación (clic en el mapa) *</label>
    <div id="map" class="rounded border mb-2" style="height: 340px;"></div>
    <div class="d-flex gap-2">
      <input class="form-control" style="max-width:220px" name="latitude" id="lat" placeholder="Latitud" readonly required>
      <input class="form-control" style="max-width:220px" name="longitude" id="lng" placeholder="Longitud" readonly required>
      <button type="button" class="btn btn-outline-primary" id="btnLocate">Usar mi ubicación</button>
      <button type="button" class="btn btn-outline-secondary" id="btnClearPoint">Limpiar</button>
    </div>
    <div class="form-text">Haz clic en el mapa para establecer el punto. Puedes ajustar con otro clic.</div>
  </div>

  <div class="col-12">
    <label class="form-label">Fotos (opcional)</label>
    <input type="file" class="form-control" name="photos[]" accept="image/*" multiple>
    <div class="form-text">Hasta 10&nbsp;MB por foto. Se adjuntan como evidencia.</div>
  </div>

  <div class="col-12 d-grid">
    <button class="btn btn-primary">Enviar reporte</button>
  </div>

  <div id="msg" class="col-12"></div>
</form>

<script>
// --- Carga de catálogos ---
async function loadProvinces(){
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  const ps = document.getElementById('provinceSelect');
  p.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ps.appendChild(o); });
}
async function loadTypes(){
  const t = await apiGet('/alertard/api/catalogs.php?resource=types');
  const wrap = document.getElementById('typesWrap');
  wrap.innerHTML = t.map(x => `
    <input type="checkbox" class="btn-check" id="type${x.id}" autocomplete="off" name="types[]" value="${x.id}">
    <label class="btn btn-sm btn-outline-primary me-1 mb-1" for="type${x.id}">${x.name}</label>
  `).join('');
}
document.getElementById('provinceSelect').addEventListener('change', async (e)=>{
  const pid = e.target.value; const ms = document.getElementById('municipalitySelect');
  const bs = document.getElementById('barrioSelect');
  ms.innerHTML = '<option value="">(Seleccione)</option>'; ms.disabled = !pid;
  bs.innerHTML = '<option value="">(Seleccione)</option>'; bs.disabled = true;
  if (!pid) return;
  const m = await apiGet('/alertard/api/catalogs.php?resource=municipalities&province_id=' + pid);
  m.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ms.appendChild(o); });
});
document.getElementById('municipalitySelect').addEventListener('change', async (e)=>{
  const mid = e.target.value; const bs = document.getElementById('barrioSelect');
  bs.innerHTML = '<option value="">(Seleccione)</option>'; bs.disabled = !mid;
  if (!mid) return;
  const b = await apiGet('/alertard/api/catalogs.php?resource=barrios&municipality_id=' + mid);
  b.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; bs.appendChild(o); });
});

// --- Mapa clickeable ---
let point = null;
const map = L.map('map').setView([18.7357,-70.1627], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution: '&copy; OpenStreetMap' }).addTo(map);
function setPoint(lat,lng){
  document.getElementById('lat').value = lat.toFixed(6);
  document.getElementById('lng').value = lng.toFixed(6);
  if (point) { map.removeLayer(point); }
  point = L.marker([lat,lng]).addTo(map);
}
map.on('click', (e)=> setPoint(e.latlng.lat, e.latlng.lng));
document.getElementById('btnLocate').addEventListener('click', ()=>{
  if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización'); return; }
  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude, lng = pos.coords.longitude;
      map.setView([lat,lng], 15); setPoint(lat,lng);
    },
    err => alert('No se pudo obtener ubicación: ' + err.message),
    { enableHighAccuracy:true, timeout:8000 }
  );
});
document.getElementById('btnClearPoint').addEventListener('click', ()=>{
  if (point) { map.removeLayer(point); point=null; }
  document.getElementById('lat').value=''; document.getElementById('lng').value='';
});

// --- Validación simple front ---
function frontValidate(fd){
  const errs = [];
  if (!fd.get('title')) errs.push('El título es obligatorio.');
  if (!fd.get('occurrence_at')) errs.push('La fecha/hora es obligatoria.');
  if (!fd.getAll('types[]').length) errs.push('Selecciona al menos un tipo.');
  if (!fd.get('latitude') || !fd.get('longitude')) errs.push('Selecciona el punto en el mapa.');
  return errs;
}

// --- Envío ---
document.getElementById('repForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);

  // social_links: cada línea -> array social_links[]
  const linksRaw = (fd.get('social_links') || '').toString().split(/\r?\n/).map(s=>s.trim()).filter(Boolean);
  fd.delete('social_links');
  linksRaw.forEach(u => fd.append('social_links[]', u));

  const errs = frontValidate(fd);
  const msg = document.getElementById('msg');
  if (errs.length){
    msg.innerHTML = `<div class="alert alert-danger">${errs.join('<br>')}</div>`;
    return;
  }

  msg.innerHTML = '<div class="alert alert-info">Enviando…</div>';
  try {
    const res = await fetch('/alertard/api/incident_create.php', { method:'POST', body: fd, credentials:'same-origin' });
    const json = await res.json();
    if (!json.ok) {
      const s = json.errors ? json.errors.join('<br>') : (json.error || 'Error inesperado');
      msg.innerHTML = `<div class="alert alert-danger">${s}</div>`;
    } else {
      msg.innerHTML = `<div class="alert alert-success">¡Reporte enviado! Quedó <strong>pendiente de validación</strong> (ID #${json.id}).</div>`;
      form.reset();
      if (point) { map.removeLayer(point); point=null; }
    }
  } catch (err) {
    msg.innerHTML = `<div class="alert alert-danger">Error de red: ${err.message}</div>`;
  }
});

// Init
(async function init(){
  await loadProvinces();
  await loadTypes();
})();
</script>

<?php require __DIR__.'/partials/footer.php'; ?>
