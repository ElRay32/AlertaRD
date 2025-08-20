<?php require __DIR__.'/partials/header.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<h4 class="mb-3">Mapa - Incidencias últimas 24h</h4>
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
const map = L.map('map').setView([18.5,-69.9], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution: '&copy; OpenStreetMap' }).addTo(map);
const markers = L.markerClusterGroup();
map.addLayer(markers);

async function loadMap() {
  const res = await apiGet('/alertard/api/incidents_last24.php');
  markers.clearLayers();
  res.data.forEach(i => {
    if (i.latitude===null || i.longitude===null) return;
    const m = L.marker([i.latitude, i.longitude]);
    m.bindPopup(`<strong>${i.title}</strong><br>${i.province||''}${i.municipality?', '+i.municipality:''}<br><button class='btn btn-sm btn-primary mt-2' onclick='openIncident(${i.id})'>Ver detalle</button>`);
    markers.addLayer(m);
  });
}
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
    e.preventDefault();
    const fd = new FormData(e.target);
    await apiPost('/alertard/api/comment_add.php', fd);
    await openIncident(id);
  });
  document.getElementById('corrForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    await apiPost('/alertard/api/correction_add.php', fd);
    alert('Corrección enviada para revisión.');
  });

  const modal = new bootstrap.Modal(document.getElementById('incidentModal'));
  modal.show();
}
loadMap();
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
