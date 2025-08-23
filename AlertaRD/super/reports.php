<?php $title='Pendientes de validación'; require __DIR__.'/../partials/header.php'; ?>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<h4 class="mb-3">Reportes pendientes</h4>

<form id="filters" class="row g-2 mb-3">
  <div class="col-md-4">
    <input class="form-control" name="q" placeholder="Buscar por título/descr.">
  </div>
  <div class="col-md-2"><input type="date" class="form-control" name="date_from"></div>
  <div class="col-md-2"><input type="date" class="form-control" name="date_to"></div>
  <div class="col-md-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Aplicar</button>
    <button type="button" class="btn btn-outline-secondary" id="btnClear">Limpiar</button>
    <button type="button" class="btn btn-outline-dark ms-auto" id="btnMerge">Unir seleccionados</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-hover align-middle" id="tbl">
    <thead>
      <tr>
        <th style="width:36px"><input type="checkbox" id="chkAll"></th>
        <th>ID</th>
        <th>Título</th>
        <th>Tipos</th>
        <th>Ubicación</th>
        <th>Ocurrió</th>
        <th>Reportero</th>
        <th style="width:220px">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<nav class="mt-2">
  <ul class="pagination" id="pager"></ul>
</nav>

<!-- Modal rechazar -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="rejectForm">
      <div class="modal-header">
        <h5 class="modal-title">Rechazar reporte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="rejectId">
        <label class="form-label">Motivo (opcional)</label>
        <textarea class="form-control" name="note" rows="4" placeholder="Ej.: reporte duplicado, información insuficiente..."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-danger" type="submit">Rechazar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal merge -->
<div class="modal fade" id="mergeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="mergeForm">
      <div class="modal-header">
        <h5 class="modal-title">Unir reportes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="mergeBody">
        <!-- radios se generan dinámicamente -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-dark" type="submit">Unir</button>
      </div>
    </form>
  </div>
</div>

<!-- Reusa el modal de detalle del mapa -->
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
const BASE = window.BASE_URL || '';
const join = (p) => (BASE + p).replace(/([^:]\/)\/+/g, '$1'); // une BASE + path sin // dobles

let currentPage = 1, currentLimit = 20;

function rowHtml(r){
  const loc = `${r.province||''}${r.municipality?', '+r.municipality:''}`;
  return `
    <tr>
      <td><input type="checkbox" class="rowChk" value="${r.id}"></td>
      <td>${r.id}</td>
      <td>${r.title||''}</td>
      <td>${r.types||''}</td>
      <td>${loc||''}</td>
      <td>${formatDateTime(r.occurrence_at)||''}</td>
      <td>${r.reporter_name||''}</td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-secondary" onclick="ver(${r.id})">Ver</button>
        <button class="btn btn-sm btn-success" onclick="pub(${r.id})">Publicar</button>
        <button class="btn btn-sm btn-danger" onclick="openReject(${r.id})">Rechazar</button>
      </td>
    </tr>`;
}

async function load(page=1){
   try{
     currentPage = page;
     const qs = new URLSearchParams(new FormData(document.getElementById('filters')));
     qs.set('page', page); qs.set('limit', currentLimit);
     const url = '/api/super_reports_pending.php?' + qs.toString();
     const res  = await apiGet('/api/super_reports_pending.php?' + qs.toString());
     console.log('[PENDIENTES]', res); // debug
     const tb = document.querySelector('#tbl tbody');
     tb.innerHTML = (res.data || []).map(rowHtml).join('') || `<tr><td colspan="8" class="text-muted">No hay pendientes.</td></tr>`;
     renderPager(res.total || 0, res.page || 1, res.limit || currentLimit);
   }catch(err){
     console.error('Error cargando pendientes:', err);
     const tb = document.querySelector('#tbl tbody');
     if (tb) tb.innerHTML = `<tr><td colspan="8" class="text-danger">Error: ${String(err.message || err)}</td></tr>`;
   }
 }

function renderPager(total, page, limit){
  const ul = document.getElementById('pager'); ul.innerHTML='';
  const pages = Math.max(1, Math.ceil(total/limit));
  for (let p=1; p<=pages; p++){
    const li = document.createElement('li');
    li.className = 'page-item' + (p===page?' active':'');
    li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
    li.onclick = (e)=>{ e.preventDefault(); load(p); };
    ul.appendChild(li);
  }
}

async function ver(id){
  const data = await apiGet('/api/incident_detail.php?id=' + id);
  document.getElementById('modalTitle').textContent = `#${data.incident.id} · ${data.incident.title}`;
  const photos = (data.photos||[]).map(p=>`<img src="${p.path_or_url}" class="img-fluid rounded me-2 mb-2" style="max-height:120px">`).join('');
  const types  = (data.types||[]).map(t=>`<span class="badge text-bg-light border me-1">${t.name}</span>`).join('');
  const links  = (data.links||[]).map(l=>`<a href="${l.url}" target="_blank">${l.platform||'link'}</a>`).join(' · ');
  const comments = (data.comments||[]).map(c=>`<div class="mb-2"><strong>${c.name}</strong> <small class="text-muted">${formatDateTime(c.created_at)}</small><br>${c.content}</div>`).join('') || '<em class="text-muted">Sin comentarios</em>';

  const html = `
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
        <div class="small"><strong>Muertos:</strong> ${data.incident.deaths??'—'}</div>
        <div class="small"><strong>Heridos:</strong> ${data.incident.injuries??'—'}</div>
        <div class="small"><strong>Pérdida RD$:</strong> ${data.incident.loss_estimate_rd??'—'}</div>
      </div>
    </div>`;
  document.getElementById('modalContent').innerHTML = html;
  new bootstrap.Modal(document.getElementById('incidentModal')).show();
}

async function pub(id){
  if (!confirm('¿Publicar este reporte?')) return;
  await apiPost('/api/super_incident_publish.php', { id });
  await load(currentPage);
}

function openReject(id){
  document.getElementById('rejectId').value = id;
  document.querySelector('#rejectForm textarea[name=note]').value = '';
  new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
document.getElementById('rejectForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  await apiPost('/api/super_incident_reject.php', fd);
  bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
  await load(currentPage);
});

// --- Merge ---
document.getElementById('btnMerge').addEventListener('click', ()=>{
  const ids = Array.from(document.querySelectorAll('.rowChk:checked')).map(x=>parseInt(x.value,10));
  if (ids.length < 2) { alert('Selecciona al menos dos reportes.'); return; }
  // cuerpo del modal con radios para elegir principal
  const body = document.getElementById('mergeBody');
  body.innerHTML = ids.map((id,idx)=>`
    <div class="form-check">
      <input class="form-check-input" type="radio" name="primary_id" id="p${id}" value="${id}" ${idx===0?'checked':''}>
      <label class="form-check-label" for="p${id}">#${id}</label>
    </div>`).join('') + `
    <input type="hidden" name="children" value="${ids.join(',')}">
    <div class="form-text mt-2">Se moverán tipos, fotos, enlaces, comentarios y correcciones al principal. Los demás quedarán como <code>merged</code>.</div>`;
  new bootstrap.Modal(document.getElementById('mergeModal')).show();
});

document.getElementById('mergeForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const primary_id = parseInt(document.querySelector('input[name=primary_id]:checked').value, 10);
  const all = document.querySelector('#mergeBody input[name=children]').value.split(',').map(x=>parseInt(x,10));
  const children = all.filter(x=>x!==primary_id);
  await apiPost('/api/super_incident_merge.php', {primary_id, children});
  bootstrap.Modal.getInstance(document.getElementById('mergeModal')).hide();
  // desmarcar todo
  document.getElementById('chkAll').checked = false;
  document.querySelectorAll('.rowChk:checked').forEach(x=>x.checked=false);
  await load(currentPage);
});

// Filtros / UI
document.getElementById('filters').addEventListener('submit', (e)=>{ e.preventDefault(); load(1); });
document.getElementById('btnClear').addEventListener('click', ()=>{
  document.getElementById('filters').reset(); load(1);
});
document.getElementById('chkAll').addEventListener('change', (e)=>{
  const chk = e.target.checked;
  document.querySelectorAll('.rowChk').forEach(x=>x.checked = chk);
});

// Init
document.addEventListener('DOMContentLoaded', () => { load(1); });
</script>

<?php require __DIR__.'/../partials/footer.php'; ?>
