<?php
  // Detalle público de una incidencia
  $title = 'Detalle de incidencia';
  require __DIR__.'/partials/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container py-4">
  <a href="<?= $BASE_URL ?>/incidents.php" class="text-decoration-none">&laquo; Volver a Incidencias</a>

  <div class="d-flex align-items-start justify-content-between mt-2">
    <h3 id="inc-title" class="mb-0">(cargando...)</h3>

    <!-- compartir -->
    <div class="d-flex gap-2">
      <button id="btnCopy" class="btn btn-sm btn-outline-secondary" type="button" title="Copiar enlace">Copiar enlace</button>
      <a id="btnWhats" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener">WhatsApp</a>
    </div>
  </div>

  <div class="text-muted mt-1" id="inc-meta"></div>

  <div id="inc-types" class="mt-2 d-flex flex-wrap gap-2"></div>

  <!-- galería -->
  <div id="gallery" class="mt-3 d-flex flex-wrap gap-2"></div>

  <div class="row mt-3 g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Descripción</h5>
          <p id="inc-desc" class="mb-0 text-wrap" style="white-space:pre-line"></p>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Ubicación</h5>
          <div id="map" style="height:320px" class="rounded border"></div>
          <div id="inc-geo" class="small text-muted mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ===== helpers =====
function fmtDate(s){
  if(!s) return '';
  // acepta "YYYY-MM-DD HH:MM:SS"
  return String(s).replace('T',' ').slice(0,16);
}
function chip(txt){ return `<span class="badge text-bg-primary">${txt}</span>`; }

// ===== mapa =====
let map, marker;
function initMap(lat,lng){
  if (!map) {
    map = L.map('map').setView([18.7357,-70.1627], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      { attribution:'&copy; OpenStreetMap' }).addTo(map);
    setTimeout(()=> map.invalidateSize(), 0); // evita mapa en blanco
  }
  if (lat && lng) {
    map.setView([lat,lng], 15);
    if (marker) map.removeLayer(marker);
    marker = L.marker([lat,lng]).addTo(map);
  }
}

// ===== render =====
function renderDetail(payload){
  const i = payload.incident;
  const types = payload.types || [];
  const photos = payload.photos || [];

  // Título
  document.getElementById('inc-title').textContent = i?.title || '(sin título)';

  // Meta: fecha y lugar
  const meta = [];
  if (i?.occurrence_at) meta.push(fmtDate(i.occurrence_at));
  const loc = [i?.province_name || i?.province, i?.municipality_name || i?.municipality].filter(Boolean).join(', ');
  if (loc) meta.push(loc);
  document.getElementById('inc-meta').textContent = meta.join(' · ');

  // Tipos
  document.getElementById('inc-types').innerHTML =
    types.length ? types.map(t => chip(t.name)).join(' ')
                 : '<span class="text-muted">(sin tipo)</span>';

  // Descripción
  document.getElementById('inc-desc').textContent = i?.description || '(sin descripción)';

  // Galería
  const gal = document.getElementById('gallery');
  if (photos.length){
    gal.innerHTML = photos.map(ph=>{
      const url = ph.path_or_url || ph.url || ph.path || '';
      return url ? `<a href="${url}" target="_blank" rel="noopener">
                      <img src="${url}" style="height:120px;width:auto;border-radius:.5rem;border:1px solid #ddd">
                    </a>` : '';
    }).join('');
  } else {
    gal.innerHTML = '<div class="text-muted">(sin fotos)</div>';
  }

  // Geo/Mapa
  const lat = i?.latitude ? parseFloat(i.latitude) : null;
  const lng = i?.longitude ? parseFloat(i.longitude) : null;
  initMap(lat, lng);
  const geoTxt = [
    loc || '',
    (lat && lng) ? `Lat: ${lat.toFixed(6)} · Lng: ${lng.toFixed(6)}` : ''
  ].filter(Boolean).join(' — ');
  document.getElementById('inc-geo').textContent = geoTxt;

  // Compartir
  const url = window.location.href;
  document.getElementById('btnWhats').href = 'https://wa.me/?text=' + encodeURIComponent(`${i?.title||'Incidencia'} - ${url}`);
  document.getElementById('btnCopy').onclick = async ()=>{
    try { await navigator.clipboard.writeText(url); alert('Enlace copiado'); } catch(e){ alert(url); }
  };
}

// ===== main =====
document.addEventListener('DOMContentLoaded', async ()=>{
  const id = Number(new URLSearchParams(location.search).get('id')||0);
  if (!id){ alert('Falta parámetro id'); location.href = (window.BASE_URL||'') + '/incidents.php'; return; }

  try {
    const res  = await fetch((window.BASE_URL||'') + '/api/incident_detail.php?id=' + id, { credentials:'same-origin' });
    const text = await res.text();            // <-- leemos texto bruto
    if (!res.ok) {                            // HTTP 4xx/5xx
      alert('HTTP '+res.status+': ' + text.slice(0,200));
      console.error('API error body:', text);
      return;
    }
    let data;
    try { data = JSON.parse(text); }          // <-- parsea JSON de forma segura
    catch(e){
      alert('Respuesta no es JSON: ' + text.slice(0,200));
      console.error('Respuesta cruda:', text);
      return;
    }

    if (data.error) {                         // <-- backend reportó error
      alert('API error: ' + data.error);
      return;
    }
    if (!data.incident) {
      document.querySelector('.container').innerHTML =
        `<div class="py-5">
           <h4>Incidencia no disponible</h4>
           <p class="text-muted">Puede que no esté publicada o fue eliminada.</p>
           <a class="btn btn-outline-primary" href="${(window.BASE_URL||'') + '/incidents.php'}">Volver</a>
         </div>`;
      return;
    }

    renderDetail(data);                        // <-- pinta el detalle
  } catch (e) {
    alert('No se pudo cargar el detalle');
    console.error(e);
  }
});

</script>

<?php require __DIR__.'/partials/footer.php'; ?>
