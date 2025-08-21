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
  <label class="form-label d-block">Fotos (opcional)</label>

  <!-- Dropzone -->
  <div id="dz" class="dropzone">
    Arrastra y suelta imágenes aquí o haz clic para seleccionar
    <div class="small text-muted mt-1">JPG, PNG, WEBP, GIF · máx. 10 imágenes</div>
  </div>

  <!-- Input real (oculto) por compatibilidad -->
  <input type="file" class="form-control mt-2 d-none" id="photosInput" accept="image/*" multiple>

  <!-- Previews -->
  <div id="photoPreview" class="mt-2"></div>

  <div class="form-text">Las imágenes se comprimen automáticamente antes de subirlas.</div>
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

// --- Envío del formulario ---
document.getElementById('repForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const form = e.target;
  const fd = new FormData();

  // Campos simples
  ['title','occurrence_at','description','province_id','municipality_id','barrio_id',
   'latitude','longitude','deaths','injuries','loss_estimate_rd'].forEach(name=>{
    const v = form.elements[name]?.value ?? '';
    if (v !== '') fd.append(name, v);
  });

  // Tipos (múltiple)
  document.querySelectorAll('input[name="types[]"]:checked').forEach(ch=>{
    fd.append('types[]', ch.value);
  });

  // Links (uno por línea -> social_links[])
  const linksRaw = (form.elements['social_links']?.value || '')
    .split(/\r?\n/).map(s=>s.trim()).filter(Boolean);
  linksRaw.forEach(u=> fd.append('social_links[]', u));

  // Validación front
  const errs = [];
  if (!fd.get('title')) errs.push('El título es obligatorio.');
  if (!fd.get('occurrence_at')) errs.push('La fecha/hora es obligatoria.');
  if (!fd.getAll('types[]').length) errs.push('Selecciona al menos un tipo.');
  if (!fd.get('latitude') || !fd.get('longitude')) errs.push('Selecciona el punto en el mapa.');
  if (selectedFiles.length > MAX_FILES) errs.push(`Máximo ${MAX_FILES} imágenes.`);
  if (errs.length){
    document.getElementById('msg').innerHTML = `<div class="alert alert-danger">${errs.join('<br>')}</div>`;
    return;
  }

  document.getElementById('msg').innerHTML = '<div class="alert alert-info">Comprimiendo imágenes…</div>';

  // Comprimir en serie para no reventar memoria (podrías paralelizar si quieres)
  for (let i=0; i<selectedFiles.length; i++){
    const f = selectedFiles[i].file;
    const compressed = await compressImage(f);
    fd.append('photos[]', compressed, compressed.name);
  }

  document.getElementById('msg').innerHTML = '<div class="alert alert-info">Enviando…</div>';

  try {
    const res = await fetch('/alertard/api/incident_create.php', {
      method:'POST', body: fd, credentials:'same-origin'
    });
    const json = await res.json();
    if (!json.ok) {
      const s = json.errors ? json.errors.join('<br>') : (json.error || 'Error inesperado');
      document.getElementById('msg').innerHTML = `<div class="alert alert-danger">${s}</div>`;
    } else {
      document.getElementById('msg').innerHTML = `<div class="alert alert-success">¡Reporte enviado! Quedó <strong>pendiente</strong> (ID #${json.id}).</div>`;
      form.reset();
      selectedFiles.forEach(it=> URL.revokeObjectURL(it.url));
      selectedFiles = []; renderPreviews();
      if (point) { map.removeLayer(point); point=null; }
    }
  } catch (err) {
    document.getElementById('msg').innerHTML = `<div class="alert alert-danger">Error de red: ${err.message}</div>`;
  }
});


// Init
(async function init(){
  await loadProvinces();
  await loadTypes();
})();

// --- Dropzone wiring ---
const dz = document.getElementById('dz');
const fileInput = document.getElementById('photosInput');

dz.addEventListener('click', ()=> fileInput.click());
fileInput.addEventListener('change', (e)=> addFiles(e.target.files));

dz.addEventListener('dragover', (e)=>{ e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', ()=> dz.classList.remove('dragover'));
dz.addEventListener('drop', (e)=>{
  e.preventDefault();
  dz.classList.remove('dragover');
  addFiles(e.dataTransfer.files);
});

// --- Fotos: manejo en cliente ---
const MAX_FILES = 10;
const ACCEPTED = ['image/jpeg','image/png','image/webp','image/gif'];
let selectedFiles = []; // {file: File, url: ObjectURL}

function addFiles(files){
  const arr = Array.from(files);
  for (const f of arr) {
    if (!ACCEPTED.includes(f.type)) continue;
    if (selectedFiles.length >= MAX_FILES) break;
    // límite duro ~25MB para no romper el navegador antes de comprimir
    if (f.size > 25*1024*1024) continue;
    const url = URL.createObjectURL(f);
    selectedFiles.push({file: f, url});
  }
  renderPreviews();
}

function renderPreviews(){
  const wrap = document.getElementById('photoPreview');
  wrap.innerHTML = '';
  selectedFiles.forEach((it, idx)=>{
    const el = document.createElement('div');
    el.className = 'preview-item';
    el.innerHTML = `
      <img src="${it.url}" alt="foto">
      <button type="button" class="preview-remove" title="Quitar" data-idx="${idx}">&times;</button>
    `;
    wrap.appendChild(el);
  });
  wrap.querySelectorAll('.preview-remove').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const i = parseInt(btn.getAttribute('data-idx'), 10);
      URL.revokeObjectURL(selectedFiles[i].url);
      selectedFiles.splice(i,1);
      renderPreviews();
    });
  });
}

// Compresión: redimensiona a máx 1600px y exporta JPEG/WEBP con calidad 0.82
async function compressImage(file, {maxDim=1600, mime='image/jpeg', quality=0.82} = {}){
  // GIF animados no se recomiendan comprimir con canvas (pierden animación)
  if (file.type === 'image/gif') return file;

  const bitmap = await createImageBitmap(file);
  let {width, height} = bitmap;
  if (width > height && width > maxDim) {
    height = Math.round(height * (maxDim / width));
    width  = maxDim;
  } else if (height >= width && height > maxDim) {
    width  = Math.round(width * (maxDim / height));
    height = maxDim;
  }

  const canvas = document.createElement('canvas');
  canvas.width = width; canvas.height = height;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(bitmap, 0, 0, width, height);

  // Para PNGs con transparencia, usa image/webp (mantiene alpha); si no, JPG
  const targetMime = (file.type === 'image/png') ? 'image/webp' : mime;

  return new Promise(resolve=>{
    canvas.toBlob(blob=>{
      // Si toBlob falla, usa el original
      resolve(blob ? new File([blob], file.name.replace(/\.(png|jpg|jpeg|webp)$/i, '') + (targetMime==='image/webp'?'.webp':'.jpg'), {type: targetMime}) : file);
    }, targetMime, quality);
  });
}

</script>

<?php require __DIR__.'/partials/footer.php'; ?>
