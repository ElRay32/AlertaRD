<?php
$title='Reportar incidencia';
require __DIR__.'/partials/header.php';
if (!in_array(($role ?? 'guest'), ['reporter','validator','admin'])){ ?>
  <div class="container py-4"><div class="alert alert-warning">Debes iniciar sesión para reportar.</div></div>
<?php require __DIR__.'/partials/footer.php'; exit; } ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container py-3">
  <h4 class="mb-3">Reportar incidencia</h4>

  <form id="repForm" class="row g-3" enctype="multipart/form-data">
    <input type="hidden" id="csrf" value="<?= csrf_token() ?>">

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

    <div class="row g-3">
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
    </div>

    <div class="col-12">
      <label class="form-label d-block">Tipo(s) de incidencia *</label>
      <div id="typesWrap" class="d-flex flex-wrap gap-2"></div>
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
      <textarea class="form-control" name="social_links" rows="3" placeholder="https://twitter.com/...
https://www.instagram.com/p/..."></textarea>
    </div>

    <div class="col-md-8">
      <label class="form-label d-block">Ubicación</label>
      <div id="map" style="height: 320px" class="rounded border"></div>
      <div class="row g-2 mt-1">
        <div class="col"><input class="form-control form-control-sm" id="lat" name="latitude" placeholder="Latitud" readonly></div>
        <div class="col"><input class="form-control form-control-sm" id="lng" name="longitude" placeholder="Longitud" readonly></div>
        <div class="col-auto d-grid"><button class="btn btn-sm btn-outline-secondary" id="btnLocate" type="button">Mi ubicación</button></div>
        <div class="col-auto d-grid"><button class="btn btn-sm btn-outline-secondary" id="btnClearPoint" type="button">Limpiar</button></div>
      </div>
      <div class="form-text">Haz clic en el mapa para fijar el punto.</div>
    </div>

    <div class="col-md-4">
      <label class="form-label d-block">Fotos (opcional)</label>
      <input type="file" class="form-control" id="photosInput" accept="image/*" multiple>
      <div id="photoPreview" class="mt-2 d-flex flex-wrap gap-2"></div>
    </div>

    <div class="col-12 d-grid">
      <button class="btn btn-primary">Enviar reporte</button>
    </div>
    <div id="msg" class="col-12"></div>
  </form>
</div>

<style>#netError{position:fixed;right:12px;bottom:12px;max-width:540px;z-index:9999;display:none}</style>
<div id="netError" class="alert alert-danger"></div>

<script>
const BASE = "<?= $BASE_URL ?? '' ?>";
const API  = BASE + "/api";
const $    = (q)=>document.querySelector(q);

function showNetError(html){ const b=$('#netError'); b.innerHTML=html; b.style.display=''; }
function hideNetError(){ $('#netError').style.display='none'; }
function esc(s){ return String(s).replace(/[<>&]/g,c=>({'<':'&lt;','>':'&gt;','&':'&amp;'}[c])); }

function o(v,t){ const e=document.createElement('option'); e.value=v; e.textContent=t; return e; }

async function expectJSON(resp, url){
  const ct=(resp.headers.get('content-type')||'').toLowerCase();
  const txt=await resp.text();
  if(!resp.ok){
    showNetError('<strong>'+resp.status+' '+resp.statusText+'</strong> '+esc(url)+'<pre class="mt-2 mb-0 small">'+esc(txt).slice(0,1000)+'</pre>');
    throw new Error('HTTP '+resp.status);
  }
  if(!ct.includes('application/json')){
    showNetError('<strong>Esperaba JSON</strong> en '+esc(url)+'<br>Content-Type: '+ct+'<pre class="mt-2 mb-0 small">'+esc(txt).slice(0,1000)+'</pre>');
    throw new Error('Not JSON');
  }
  hideNetError();
  return JSON.parse(txt);
}
async function getJSON(url){
  const r = await fetch(url, {credentials:'same-origin', headers:{'Accept':'application/json'}});
  return expectJSON(r, url);
}

// ---------- Catálogos: Provincias → Municipios → Barrios ----------
let C_PROV=null, C_MUNI=null, C_BARR=null;

async function loadProvinces(){
  try{ C_PROV=(await getJSON(API+'/provinces_list.php')).data||[]; }
  catch(_){ C_PROV=(await getJSON(API+'/catalog_list.php?entity=provinces')).data||[]; }
  const sel=$('#provinceSelect'); sel.innerHTML='<option value="">(Seleccione)</option>';
  C_PROV.forEach(x=> sel.appendChild(o(x.id, x.name))); sel.disabled=false;
}
async function ensureMunicipalities(){ if(C_MUNI!==null) return;
  try{ C_MUNI=(await getJSON(API+'/municipalities_list.php')).data||[]; }catch(_){ C_MUNI=null; } }
async function ensureBarrios(){ if(C_BARR!==null) return;
  try{ C_BARR=(await getJSON(API+'/barrios_list.php')).data||[]; }catch(_){ C_BARR=null; } }

async function onProvinceChange(){
  const pid=$('#provinceSelect').value;
  const ms=$('#municipalitySelect'), bs=$('#barrioSelect');
  ms.innerHTML='<option value="">(Seleccione)</option>'; bs.innerHTML='<option value="">(Seleccione)</option>'; bs.disabled=true;
  await ensureMunicipalities();
  if(!pid){ ms.disabled=true; return; }
  ms.disabled=false;
  if(Array.isArray(C_MUNI)){ C_MUNI.filter(m=> String(m.province_id)===String(pid)).forEach(m=> ms.appendChild(o(m.id,m.name))); }
  else {
    const j=await getJSON(API+'/catalog_list.php?entity=municipalities&province_id='+encodeURIComponent(pid));
    (j.data||[]).forEach(m=> ms.appendChild(o(m.id,m.name)));
  }
}
async function onMunicipalityChange(){
  const mid=$('#municipalitySelect').value;
  const bs=$('#barrioSelect'); bs.innerHTML='<option value="">(Seleccione)</option>';
  if(!mid){ bs.disabled=true; return; } bs.disabled=false;
  await ensureBarrios();
  if(Array.isArray(C_BARR)){ C_BARR.filter(b=> String(b.municipality_id)===String(mid)).forEach(b=> bs.appendChild(o(b.id,b.name))); }
  else {
    const j=await getJSON(API+'/catalog_list.php?entity=barrios&municipality_id='+encodeURIComponent(mid));
    (j.data||[]).forEach(b=> bs.appendChild(o(b.id,b.name)));
  }
}
$('#provinceSelect').addEventListener('change', onProvinceChange);
$('#municipalitySelect').addEventListener('change', onMunicipalityChange);

// ---------- Tipos ----------

async function getJSON(url){
  const r = await fetch(url, {credentials:'same-origin', headers:{'Accept':'application/json'}});
  const txt = await r.text();
  if (!r.ok) throw new Error(txt || ('HTTP '+r.status));
  return JSON.parse(txt);
}

let TYPES_COUNT = 0;
async function loadTypesForReport(){
  // 1) intenta tu nueva API con seed=1 por si no hay tabla o está vacía
  let res = await getJSON(API + '/types_for_report.php?seed=1');
  const wrap = document.getElementById('typesWrap');
  wrap.innerHTML = '';
  (res.data || []).forEach(t => {
    const id = 'type_' + t.id;
    wrap.insertAdjacentHTML('beforeend', `
      <input type="checkbox" class="btn-check" id="${id}" autocomplete="off" name="types[]" value="${t.id}">
      <label class="btn btn-sm btn-outline-primary" for="${id}">${t.name}</label>
    `);
  });
  TYPES_COUNT = (res.data || []).length;
  if (TYPES_COUNT === 0) {
    wrap.innerHTML = '<div class="text-muted">No hay tipos configurados.</div>';
  }
}

// Llama a esta función cuando cargue la página de Reportar:
document.addEventListener('DOMContentLoaded', loadTypesForReport);

// ---------- Mapa ----------
let point=null;
const map=L.map('map').setView([18.7357,-70.1627],8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'&copy; OpenStreetMap' }).addTo(map);
function setPoint(lat,lng){ document.getElementById('lat').value=lat.toFixed(6); document.getElementById('lng').value=lng.toFixed(6); if(point) map.removeLayer(point); point=L.marker([lat,lng]).addTo(map); }
map.on('click',(e)=> setPoint(e.latlng.lat,e.latlng.lng));
document.getElementById('btnLocate').addEventListener('click',()=>{
  if(!navigator.geolocation) return alert('Tu navegador no soporta geolocalización');
  navigator.geolocation.getCurrentPosition(pos=>{ const {latitude:lat, longitude:lng}=pos.coords; map.setView([lat,lng],15); setPoint(lat,lng); },
    err=>alert('No se pudo obtener ubicación: '+err.message), {enableHighAccuracy:true, timeout:8000});
});
document.getElementById('btnClearPoint').addEventListener('click',()=>{ if(point){map.removeLayer(point);point=null;} document.getElementById('lat').value=''; document.getElementById('lng').value=''; });

// ---------- Envío ----------
function frontValidate(fd){
  const e=[];
  if(!fd.get('title')) e.push('El título es obligatorio.');
  if(!fd.get('occurrence_at')) e.push('La fecha/hora es obligatoria.');
  if(!fd.getAll('types[]').length) e.push('Selecciona al menos un tipo.');
  if(!fd.get('latitude') || !fd.get('longitude')) e.push('Selecciona el punto en el mapa.');
  return e;
}

async function postIncident(fd){
  const token=document.getElementById('csrf').value;
  // 1) multipart (FormData)
  let resp = await fetch(API+'/incident_submit.php', {
    method:'POST', body: fd, credentials:'same-origin',
    headers:{'Accept':'application/json','X-CSRF-Token': token}
  });
  try { return await expectJSON(resp, API+'/incident_submit.php'); }
  catch(_){
    // 2) fallback JSON
    const obj={}; for(const [k,v] of fd.entries()){ if(k.endsWith('[]')){ const kk=k.slice(0,-2); (obj[kk]||(obj[kk]=[])).push(v); } else if(obj[k]!==undefined){ obj[k]=[].concat(obj[k],v); } else { obj[k]=v; } }
    resp = await fetch(API+'/incident_submit.php', {
      method:'POST', credentials:'same-origin',
      headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-Token': token},
      body: JSON.stringify(obj)
    });
    return await expectJSON(resp, API+'/incident_submit.php');
  }
}

document.getElementById('repForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const form=e.target, fd=new FormData();
  ['title','occurrence_at','description','province_id','municipality_id','barrio_id','latitude','longitude','deaths','injuries','loss_estimate_rd'].forEach(n=>{ const v=form.elements[n]?.value ?? ''; if(v!=='') fd.append(n,v); });
  document.querySelectorAll('input[name="types[]"]:checked').forEach(ch=> fd.append('types[]', ch.value));
  (form.elements['social_links']?.value || '').split(/\r?\n/).map(s=>s.trim()).filter(Boolean).forEach(u=> fd.append('social_links[]', u));
  // fotos
  const files=document.getElementById('photosInput').files; for(const f of files) fd.append('photos[]', f);
  // csrf en dos nombres (por si acaso)
  const token=document.getElementById('csrf').value; fd.append('csrf_token', token); fd.append('csrf', token);

  const errs=frontValidate(fd);
  if(errs.length){ document.getElementById('msg').innerHTML='<div class="alert alert-danger">'+errs.join('<br>')+'</div>'; return; }
  document.getElementById('msg').innerHTML='<div class="alert alert-info">Enviando…</div>';

  try{
    const json = await postIncident(fd);
    if(!json.ok) throw new Error(json.error || (json.errors && json.errors.join(', ')) || 'Error');
    document.getElementById('msg').innerHTML='<div class="alert alert-success">¡Reporte enviado! ID #'+json.id+'</div>';

    form.reset(); document.getElementById('photoPreview').innerHTML='';
    document.getElementById('municipalitySelect').innerHTML='<option value="">(Seleccione)</option>'; document.getElementById('municipalitySelect').disabled=true;
    document.getElementById('barrioSelect').innerHTML='<option value="">(Seleccione)</option>'; document.getElementById('barrioSelect').disabled=true;

    setTimeout(()=>{ window.location.replace(BASE + '/index.php'); }, 600);

    await loadProvinces(); await loadTypesForReport();

    if(point){map.removeLayer(point);point=null;} document.getElementById('lat').value=''; document.getElementById('lng').value='';
  }catch(err){
    document.getElementById('msg').innerHTML='<div class="alert alert-danger">'+err.message+'</div>';
  }
});

(async function init(){ await loadProvinces(); await loadTypesForReport(); })();
</script>

<?php require __DIR__.'/partials/footer.php'; ?>
