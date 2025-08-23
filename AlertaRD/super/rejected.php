<?php
require __DIR__.'/../partials/header.php';
if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin') {
  echo '<div class="container py-4"><div class="alert alert-danger">Acceso restringido.</div></div>';
  require __DIR__.'/../partials/footer.php'; exit;
}
?>
<div class="container py-4">
  <h4 class="mb-3">Reportes rechazados</h4>

  <form id="filters" class="row g-2 mb-3">
    <div class="col-sm-4">
      <input type="text" name="q" class="form-control" placeholder="Buscar por título o descripción">
    </div>
    <div class="col-sm-2 d-grid">
      <button class="btn btn-primary" type="submit">Buscar</button>
    </div>
    <div class="col-sm-2 d-grid">
      <button class="btn btn-outline-secondary" type="button" id="btnClear">Limpiar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm" id="tbl">
      <thead>
        <tr>
          <th><input type="checkbox" id="chkAll"></th>
          <th>ID</th>
          <th>Título</th>
          <th>Tipos</th>
          <th>Ubicación</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody><tr><td colspan="7">Cargando...</td></tr></tbody>
    </table>
  </div>
  <div id="pager" class="d-flex justify-content-between align-items-center"></div>
</div>

<script>
let currentPage = 1, currentLimit = 20;

function formatDateTime(s){
  if (!s) return '';
  s = String(s).replace('T',' ').replace('.000000','');
  return s.length >= 16 ? s.slice(0,16) : s;
}

// POST robusto: usa apiPost si existe; si no, usa fetch y añade CSRF.
async function postCompat(url, fdOrObj){
  if (typeof apiPost === 'function') {
    // apiPost acepta FormData u objeto (según tu footer)
    return apiPost(url, fdOrObj instanceof FormData ? fdOrObj : Object(fdOrObj));
  }
  let fd = fdOrObj instanceof FormData ? fdOrObj : new FormData();
  if (!(fdOrObj instanceof FormData) && fdOrObj && typeof fdOrObj === 'object') {
    for (const [k,v] of Object.entries(fdOrObj)) fd.append(k, v);
  }
  if (window.CSRF) fd.append('csrf', window.CSRF);
  const r = await fetch((window.BASE_URL||'') + url, { method:'POST', body: fd, credentials:'same-origin' });
  const txt = await r.text();
  try { const j = JSON.parse(txt); if (!r.ok) throw new Error(j.error || ('HTTP '+r.status)); return j; }
  catch(e){ throw new Error('Respuesta no es JSON: ' + txt.slice(0,200)); }
}

function rowHtml(r){
  const loc = [r.province, r.municipality].filter(Boolean).join(', ');
  const verUrl  = (window.BASE_URL||'') + '/incident.php?id=' + r.id;
  const editUrl = (window.BASE_URL||'') + '/super/incident_edit.php?id=' + r.id; // cambia si tu editor tiene otro nombre

  return `
    <tr data-row-id="${r.id}">
      <td><input type="checkbox" class="rowChk" value="${r.id}"></td>
      <td>${r.id}</td>
      <td>${r.title||''}</td>
      <td>${r.types||'(sin tipo)'}</td>
      <td>${loc||'(sin ubicación)'}</td>
      <td>${formatDateTime(r.created_at||r.occurrence_at)}</td>
      <td class="text-end d-flex gap-1 justify-content-end flex-wrap">
        <a  class="btn btn-sm btn-outline-secondary" href="${verUrl}"  target="_blank" rel="noopener">Ver</a>
        <a  class="btn btn-sm btn-outline-primary"   href="${editUrl}">Editar</a>
        <button type="button" class="btn btn-sm btn-success        btn-publish" data-id="${r.id}">Publicar</button>
        <button type="button" class="btn btn-sm btn-outline-primary btn-pending"  data-id="${r.id}">A Pendiente</button>
        <button type="button" class="btn btn-sm btn-danger         btn-del"      data-id="${r.id}">Eliminar</button>
      </td>
    </tr>`;
}

function renderPager(total, page, limit){
  const pages = Math.max(1, Math.ceil(total/limit));
  const left  = `<button class="btn btn-sm btn-outline-secondary" ${page<=1?'disabled':''} onclick="load(${page-1})">«</button>`;
  const right = `<button class="btn btn-sm btn-outline-secondary" ${page>=pages?'disabled':''} onclick="load(${page+1})">»</button>`;
  document.getElementById('pager').innerHTML =
    `<div>Total: ${total}</div><div class="d-flex gap-2 align-items-center">${left}<span>Página ${page}/${pages}</span>${right}</div>`;
}

async function load(page=1){
  currentPage = page;
  const tb = document.querySelector('#tbl tbody');
  tb.innerHTML = `<tr><td colspan="7">Cargando…</td></tr>`;
  try {
    const qs = new URLSearchParams(new FormData(document.getElementById('filters')));
    qs.set('page', page); qs.set('limit', currentLimit);
    const res = await apiGet('/api/super_reports_rejected.php?' + qs.toString());
    tb.innerHTML = (res.data||[]).map(rowHtml).join('') ||
      `<tr><td colspan="7" class="text-muted">No hay reportes rechazados.</td></tr>`;
    renderPager(res.total||0, res.page||1, res.limit||currentLimit);
  } catch (err) {
    console.error('Error al cargar rechazados:', err);
    const msg = (err && err.message) ? err.message : String(err||'');
    tb.innerHTML = `<tr><td colspan="7" class="text-danger">Error cargando la lista:<br><pre style="white-space:pre-wrap">${msg.slice(0,1000)}</pre></td></tr>`;
  }
}

// Un solo listener para los 3 botones (publicar / a pendiente / eliminar)
document.addEventListener('click', async (e)=>{
  const btn = e.target.closest('.btn-publish, .btn-pending, .btn-del');
  if (!btn) return;

  const id = Number(btn.dataset.id);
  if (!id) return alert('ID inválido');

  let status, msg, hard = 0;
  if (btn.classList.contains('btn-publish')) { status = 'published'; msg = '¿Publicar este reporte?'; }
  else if (btn.classList.contains('btn-pending')) { status = 'pending'; msg = '¿Mover este reporte a Pendiente?'; }
  else { status = 'deleted'; msg = '¿Eliminar DEFINITIVAMENTE este reporte y todo lo relacionado?'; hard = 1; }

  if (!confirm(msg)) return;

  const fd = new FormData();
  fd.append('id', id);
  fd.append('status', status);
  if (hard) fd.append('hard', '1'); // 👈 aquí pedimos el borrado físico

  // usa tu helper; si no, cambia por fetch
  const res = await apiPost('/api/super_incident_change_status.php', fd);
  if (!res || res.ok !== true) return alert('Error: ' + (res?.error || 'desconocido'));

  // quita la fila del DOM
  document.querySelector(`tr[data-row-id="${id}"]`)?.remove();
  // o recarga:  if (typeof load==='function') load(currentPage);
});


// filtros y seleccionar todos
document.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('filters').addEventListener('submit', (e)=>{ e.preventDefault(); load(1); });
  document.getElementById('btnClear').addEventListener('click', ()=>{ document.getElementById('filters').reset(); load(1); });
  document.getElementById('chkAll').addEventListener('change', (e)=>{
    const on = e.target.checked;
    document.querySelectorAll('.rowChk').forEach(x=> x.checked = on);
  });
  load(1);
});
</script>


<?php require __DIR__.'/../partials/footer.php'; ?>
