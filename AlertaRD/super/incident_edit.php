<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php
require __DIR__.'/../partials/header.php';
if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin') {
  echo '<div class="container py-4"><div class="alert alert-danger">Acceso restringido.</div></div>';
  require __DIR__.'/../partials/footer.php'; exit;
}
$id = (int)($_GET['id'] ?? 0);
?>
<div class="container py-4">
  <h4 class="mb-3">Editar reporte #<?= $id ?></h4>

  <form id="editForm" class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Título</label>
      <input class="form-control" name="title">
    </div>
    <div class="col-md-4">
      <label class="form-label">Fecha y hora</label>
      <input type="datetime-local" class="form-control" name="occurrence_at">
    </div>

    <div class="col-12">
      <label class="form-label">Descripción</label>
      <textarea class="form-control" name="description" rows="4"></textarea>
    </div>

    <div class="col-md-4">
      <label class="form-label">Provincia</label>
      <select class="form-select" id="provinceSelect" name="province_id">
        <option value="">Provincia</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Municipio</label>
      <select class="form-select" id="municipalitySelect" name="municipality_id" disabled>
        <option value="">Municipio</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Sector/Barrio</label>
      <select class="form-select" id="barrioSelect" name="barrio_id" disabled>
        <option value="">Sector/Barrio</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Latitud</label>
      <input class="form-control" name="latitude">
    </div>
    <div class="col-md-4">
      <label class="form-label">Longitud</label>
      <input class="form-control" name="longitude">
    </div>
    <div class="col-md-4">
      <label class="form-label">Pérdidas RD$</label>
      <input class="form-control" name="loss_estimate_rd">
    </div>

    <div class="col-md-4">
      <label class="form-label">Muertos</label>
      <input type="number" min="0" class="form-control" name="deaths">
    </div>
    <div class="col-md-4">
      <label class="form-label">Heridos</label>
      <input type="number" min="0" class="form-control" name="injuries">
    </div>

    <div class="col-12">
      <label class="form-label d-block">Tipo(s) de incidencia</label>
      <div id="typesWrap" class="d-flex flex-wrap gap-2"></div>
    </div>

    <!-- Ubicación -->
<div class="col-md-8">
  <label class="form-label d-block">Ubicación</label>
  <div id="map" style="height: 320px" class="rounded border"></div>
  <div class="row g-2 mt-1">
    <div class="col"><input class="form-control" id="lat" name="latitude" placeholder="Latitud"></div>
    <div class="col"><input class="form-control" id="lng" name="longitude" placeholder="Longitud"></div>
    <div class="col-auto d-grid"><button class="btn btn-sm btn-outline-secondary" id="btnLocate" type="button">Mi ubicación</button></div>
    <div class="col-auto d-grid"><button class="btn btn-sm btn-outline-secondary" id="btnClearPoint" type="button">Limpiar</button></div>
  </div>
  <div class="form-text">Haz clic en el mapa para fijar el punto.</div>
</div>

<!-- Fotos -->
<div class="col-md-4">
  <label class="form-label d-block">Fotos</label>
  <!-- galería de ya existentes -->
  <div id="photoGallery" class="mb-2 d-flex flex-wrap gap-2"></div>

  <!-- agregar más (opcional) -->
  <input type="file" class="form-control" id="photosInput" accept="image/*" multiple>
  <div id="photoPreview" class="mt-2 d-flex flex-wrap gap-2"></div>
  <div class="mt-2">
    <button class="btn btn-sm btn-outline-primary" type="button" id="btnUploadMore">Subir nuevas fotos</button>
  </div>
</div>

    <div class="col-12 d-flex gap-2">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-outline-secondary" href="<?= $BASE_URL ?>/super/reports.php">Cancelar</a>
      <button class="btn btn-outline-danger" id="btnDelete" type="button">Eliminar</button>
    </div>
  </form>
</div>

<script>
// ID desde PHP o desde la URL
const ID = Number(<?= isset($id) ? (int)$id : 0 ?>) || Number(new URLSearchParams(location.search).get('id')||0);
const BASE = window.BASE_URL || '';

function setSelect(sel, items){
  sel.innerHTML = '<option value=""></option>';
  (items||[]).forEach(it=>{
    const o = document.createElement('option');
    o.value = it.id; o.textContent = it.name;
    sel.appendChild(o);
  });
  sel.disabled = false;
}

async function loadProvinces(){
  const r = await apiGet('/api/catalog_list.php?entity=provinces');
  setSelect(document.getElementById('provinceSelect'), r.data);
}

async function loadMunicipalities(pid){
  const r = await apiGet('/api/catalog_list.php?entity=municipalities&province_id='+pid);
  setSelect(document.getElementById('municipalitySelect'), r.data);
}

async function loadBarrios(mid){
  const r = await apiGet('/api/catalog_list.php?entity=barrios&municipality_id='+mid);
  setSelect(document.getElementById('barrioSelect'), r.data);
}

async function loadTypes(selected=[]){
  const r = await apiGet('/api/catalog_list.php?entity=types');
  const wrap = document.getElementById('typesWrap'); wrap.innerHTML='';
  for (const t of (r.data||[])) {
    const id = 'type_'+t.id;
    wrap.insertAdjacentHTML('beforeend', `
      <input type="checkbox" class="btn-check" id="${id}" autocomplete="off" name="types[]" value="${t.id}">
      <label class="btn btn-sm btn-outline-primary" for="${id}">${t.name}</label>
    `);
  }
  // marcar los ya seleccionados
  for (const v of selected){
    const ch = document.querySelector('input[name="types[]"][value="'+v+'"]');
    if (ch) ch.checked = true;
  }
}

async function prefFill(){
  const data = await apiGet('/api/incident_detail.php?id=' + ID);
  const i = data.incident;
  if (!i) { alert('Reporte no encontrado'); return; }

  // Básicos
  document.querySelector('input[name=title]').value              = i.title || '';
  document.querySelector('textarea[name=description]').value     = i.description || '';
  document.querySelector('input[name=occurrence_at]').value      = (i.occurrence_at||'').replace(' ','T').slice(0,16);
  document.querySelector('input[name=loss_estimate_rd]').value   = i.loss_estimate_rd ?? '';
  document.querySelector('input[name=deaths]').value             = i.deaths ?? '';
  document.querySelector('input[name=injuries]').value           = i.injuries ?? '';

  // Geo + selects en cascada
  await loadProvinces();
  if (i.province_id) {
    document.getElementById('provinceSelect').value = i.province_id;
    await loadMunicipalities(i.province_id);
  }
  if (i.municipality_id) {
    document.getElementById('municipalitySelect').value = i.municipality_id;
    await loadBarrios(i.municipality_id);
  }
  if (i.barrio_id) {
    document.getElementById('barrioSelect').value = i.barrio_id;
  }

  // Mapa
  const lat = i.latitude  ? parseFloat(i.latitude)  : null;
  const lng = i.longitude ? parseFloat(i.longitude) : null;
  initMap(lat, lng);

  // Tipos
  const selectedTypes = (data.types||[]).map(t => t.id);
  await loadTypes(selectedTypes);

  // Fotos
  renderPhotos(data.photos);

}

document.addEventListener('DOMContentLoaded', () => {
  prefFill();

  document.getElementById('provinceSelect').addEventListener('change', async (e)=>{
    const v = e.target.value || '';
    document.getElementById('municipalitySelect').innerHTML = '<option></option>';
    document.getElementById('municipalitySelect').disabled = true;
    document.getElementById('barrioSelect').innerHTML = '<option></option>';
    document.getElementById('barrioSelect').disabled = true;
    if (v) await loadMunicipalities(v);
  });

  document.getElementById('municipalitySelect').addEventListener('change', async (e)=>{
    const v = e.target.value || '';
    document.getElementById('barrioSelect').innerHTML = '<option></option>';
    document.getElementById('barrioSelect').disabled = true;
    if (v) await loadBarrios(v);
  });

  document.getElementById('editForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = { id: ID };
    fd.forEach((v,k)=>{
      if (k==='types[]') {
        (payload.types ||= []).push(parseInt(v,10));
      } else {
        payload[k] = v;
      }
    });
    const res = await apiPost('/api/super_incident_update.php', payload);
    if (res.ok) {
      alert('Guardado');
      window.location.href = BASE + '/super/reports.php';
    }
  });

  document.getElementById('btnDelete').addEventListener('click', async ()=>{
    if (!confirm('¿Eliminar este reporte?')) return;
    await apiPost('/api/super_incident_delete.php', { id: ID });
    window.location.href = BASE + '/super/reports.php';
  });
});

// ====== MAPA ======
let map, point;
function initMap(lat, lng){
  if (!map) {
    map = L.map('map').setView([18.7357,-70.1627], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      { attribution:'&copy; OpenStreetMap' }).addTo(map);
    map.on('click', (e)=> setPoint(e.latlng.lat, e.latlng.lng));
    document.getElementById('btnLocate').addEventListener('click', ()=>{
      if(!navigator.geolocation) return alert('Tu navegador no soporta geolocalización');
      navigator.geolocation.getCurrentPosition(pos=>{
        const { latitude:lat, longitude:lng } = pos.coords;
        map.setView([lat,lng], 15); setPoint(lat,lng);
      }, err=>alert('No se pudo obtener ubicación: '+err.message), {enableHighAccuracy:true, timeout:8000});
    });
    document.getElementById('btnClearPoint').addEventListener('click', ()=>{
      if (point){ map.removeLayer(point); point=null; }
      document.getElementById('lat').value=''; document.getElementById('lng').value='';
    });
  }
  if (lat && lng){ map.setView([lat,lng], 15); setPoint(lat,lng); }

  setTimeout(()=> map.invalidateSize(), 0);
}
function setPoint(lat,lng){
  document.getElementById('lat').value = lat;
  document.getElementById('lng').value = lng;
  if (point) map.removeLayer(point);
  point = L.marker([lat,lng]).addTo(map);
}

// ====== FOTOS ======
function renderPhotos(photos){
  const gal = document.getElementById('photoGallery');
  gal.innerHTML = '';
  (photos||[]).forEach(ph=>{
    const url = ph.path_or_url || ph.url || ph.path || '';
    if (!url) return;
    gal.insertAdjacentHTML('beforeend', `
      <div class="position-relative">
        <img src="${url}" style="height:80px;width:auto;border:1px solid #ddd;border-radius:.5rem">
      </div>
    `);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!ID) { alert('Falta parámetro id'); return; }
  prefFill();
});


</script>

<?php require __DIR__.'/../partials/footer.php'; ?>
