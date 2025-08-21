<?php
require __DIR__.'/partials/header.php';
$id = (int)($_GET['id'] ?? 0);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div id="content" class="d-none">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/alertard/incidents.php">Incidencias</a></li>
      <li class="breadcrumb-item active" id="crumbTitle">Detalle</li>
    </ol>
  </nav>

  <h4 id="title" class="mb-1"></h4>
  <div class="text-muted small mb-3" id="meta"></div>

  <div class="row g-3">
    <div class="col-md-8">
      <div id="map" style="height: 380px" class="rounded border mb-3"></div>
      <div id="desc" class="mb-3"></div>
      <div id="links" class="mb-3"></div>
      <div id="photos" class="mb-3"></div>
    </div>
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header">Datos</div>
        <div class="card-body small" id="facts"></div>
      </div>
      <div class="card mb-3">
        <div class="card-header">Comentarios</div>
        <div class="card-body" style="max-height: 260px; overflow:auto" id="comments"></div>
        <div class="card-footer">
          <form id="commentForm">
            <input type="hidden" name="incident_id" value="<?php echo $id; ?>">
            <textarea class="form-control mb-2" name="content" placeholder="Escribe un comentario" required></textarea>
            <button class="btn btn-sm btn-primary w-100">Comentar</button>
          </form>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Sugerir corrección</div>
        <div class="card-body">
          <form id="corrForm">
            <input type="hidden" name="incident_id" value="<?php echo $id; ?>">
            <div class="row g-2">
              <div class="col-6"><input class="form-control" type="number" min="0" name="new_deaths" placeholder="Muertos"></div>
              <div class="col-6"><input class="form-control" type="number" min="0" name="new_injuries" placeholder="Heridos"></div>
              <div class="col-12"><input class="form-control" type="number" step="0.01" name="new_loss_estimate_rd" placeholder="Pérdida RD$"></div>
              <div class="col-6"><input class="form-control" type="number" step="0.000001" name="new_latitude" placeholder="Latitud"></div>
              <div class="col-6"><input class="form-control" type="number" step="0.000001" name="new_longitude" placeholder="Longitud"></div>
              <div class="col-12"><input class="form-control" name="note" placeholder="Nota (opcional)"></div>
            </div>
            <button class="btn btn-sm btn-outline-secondary w-100 mt-2">Enviar corrección</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// --- Utilidades de iconos (mismo bloque que en map.php, resumido) ---
const PALETTE = ['#d81b60','#1e88e5','#43a047','#f4511e','#6d4c41','#8e24aa','#3949ab','#00897b','#fb8c00','#7cb342'];
function colorForType(name){ let h=0; for (const ch of (name||'')) { h=(h*31+ch.charCodeAt(0))>>>0;} return PALETTE[h%PALETTE.length]; }
function slugify(s){ return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,''); }
const TYPE_ALIASES = { 'asalto':'robo','riña':'pelea','rinna':'pelea','inundacion':'desastre','incendio':'desastre','huracan':'desastre','terremoto':'desastre' };
const TYPE_ICONS = {
  'accidente': (hex)=>`<svg viewBox="0 0 64 64" width="28" height="28" xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke="${hex}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 40 h36 l6-10 h8"/><circle cx="18" cy="48" r="6" fill="${hex}"/><circle cx="42" cy="48" r="6" fill="${hex}"/><path d="M28 22 l6-8 M30 14 l8 6 M30 14 l-3-9"/></g></svg>`,
  'pelea':     (hex)=>`<svg viewBox="0 0 64 64" width="28" height="28" xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke="${hex}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M14 38 c8-12 18-12 26 0"/><path d="M50 26 c-8 12-18 12-26 0"/><path d="M28 32 l8 0"/></g></svg>`,
  'robo':      (hex)=>`<svg viewBox="0 0 64 64" width="28" height="28" xmlns="http://www.w3.org/2000/svg"><g fill="${hex}" stroke="${hex}" stroke-width="2"><path d="M8 24 q24-12 48 0 v8 q-24 12-48 0 z"/><circle cx="24" cy="28" r="5" fill="#fff"/><circle cx="40" cy="28" r="5" fill="#fff"/><circle cx="24" cy="28" r="2"/><circle cx="40" cy="28" r="2"/></g></svg>`,
  'desastre':  (hex)=>`<svg viewBox="0 0 64 64" width="28" height="28" xmlns="http://www.w3.org/2000/svg"><polygon points="32,6 58,54 6,54" fill="${hex}" /><rect x="30" y="22" width="4" height="18" fill="#fff"/><circle cx="32" cy="46" r="2.5" fill="#fff"/></svg>`
};
function dotSVG(hex){ return `<svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="7" fill="${hex}" stroke="#fff" stroke-width="2"/></svg>`; }
function iconForType(typeName){
  const slug = TYPE_ALIASES[slugify(typeName)] || slugify(typeName);
  const color = colorForType(typeName||'');
  const html = TYPE_ICONS[slug] ? TYPE_ICONS[slug](color) : dotSVG(color);
  return L.divIcon({ className:'pin-svg', html, iconSize:[28,28], iconAnchor:[14,28] });
}

const ID = <?php echo $id; ?>;
if (!ID) { document.body.innerHTML = '<div class="container py-4"><div class="alert alert-danger">Falta ID</div></div>'; }

async function loadDetail(){
  const data = await apiGet('/alertard/api/incident_detail.php?id=' + ID);
  const i = data.incident;
  if (!i || i.status!=='published') {
    document.body.innerHTML = '<div class="container py-4"><div class="alert alert-warning">Incidencia no disponible.</div></div>'; return;
  }
  // Título y meta
  document.getElementById('crumbTitle').textContent = i.title || `#${i.id}`;
  document.getElementById('title').textContent = i.title || `#${i.id}`;
  document.getElementById('meta').innerHTML = `
    <span class="me-2">${i.province||''}${i.municipality?', '+i.municipality:''}</span>
    <span class="text-muted">${formatDateTime(i.occurrence_at)||''}</span>
  `;

  // Descripción / Fotos / Links
  document.getElementById('desc').textContent = i.description || '';
  const photos = (data.photos||[]).map(p=>`<img src="${p.path_or_url}" class="img-fluid rounded me-2 mb-2" style="max-height:160px">`).join('');
  document.getElementById('photos').innerHTML = photos ? `<h6>Fotos</h6>${photos}` : '';
  const links  = (data.links||[]).map(l=>`<a href="${l.url}" target="_blank">${l.platform||l.url}</a>`).join(' · ');
  document.getElementById('links').innerHTML = links ? `<h6>Enlaces</h6><div class="small">${links}</div>` : '';

  // Datos
  const types = (data.types||[]).map(t=>`<span class="badge text-bg-light border me-1">${t.name}</span>`).join('');
  document.getElementById('facts').innerHTML = `
    <div><strong>Tipos:</strong> ${types||'—'}</div>
    <div><strong>Muertos:</strong> ${i.deaths??'—'} · <strong>Heridos:</strong> ${i.injuries??'—'}</div>
    <div><strong>Pérdida RD$:</strong> ${i.loss_estimate_rd??'—'}</div>
    <div><strong>Coordenadas:</strong> ${i.latitude??'—'}, ${i.longitude??'—'}</div>
  `;

  // Comentarios
  const comments = (data.comments||[]).map(c=>`
    <div class="mb-2">
      <strong>${c.name}</strong> <small class="text-muted">${formatDateTime(c.created_at)}</small><br>${c.content}
    </div>`).join('') || '<em class="text-muted">Sin comentarios</em>';
  document.getElementById('comments').innerHTML = comments;

  // Mapa
  const map = L.map('map').setView([i.latitude||18.7357, i.longitude||-70.1627], i.latitude?14:7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution: '&copy; OpenStreetMap' }).addTo(map);
  if (i.latitude && i.longitude) {
    const tPrim = (data.types||[])[0]?.name || '';
    L.marker([i.latitude, i.longitude], {icon: iconForType(tPrim)}).addTo(map);
  }

  // Formularios
  document.getElementById('commentForm').addEventListener('submit', async (e)=>{
    e.preventDefault(); const fd = new FormData(e.target);
    await apiPost('/alertard/api/comment_add.php', fd);
    e.target.reset();
    await loadDetail();
  });
  document.getElementById('corrForm').addEventListener('submit', async (e)=>{
    e.preventDefault(); const fd = new FormData(e.target);
    await apiPost('/alertard/api/correction_add.php', fd);
    e.target.reset();
    alert('Corrección enviada para revisión.');
  });

  document.getElementById('content').classList.remove('d-none');
}

loadDetail();
</script>
<?php require __DIR__.'/partials/footer.php'; ?>

