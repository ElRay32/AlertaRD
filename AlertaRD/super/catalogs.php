<?php
// /AlertaRD/super/catalogs.php  (Catálogos con header propio, simple y sin <base>)
require_once __DIR__ . '/../api/helpers.php';
start_session_safe();
require_role(['validator','admin']);
$title = 'Catálogos';
require __DIR__ . '/../partials/header_catalog.php';
?>
<input type="hidden" id="csrf" value="<?= csrf_token() ?>">

<div class="container py-4">
  <div id="alertBox" style="display:none"></div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Catálogos</h4>
    <div class="d-flex gap-2">
      <input id="search" class="form-control form-control-sm" placeholder="Buscar..." style="width:220px">
      <button class="btn btn-sm btn-primary" id="btnNew">Nuevo</button>
    </div>
  </div>

  <ul class="nav nav-tabs mb-3" id="tabs">
    <li class="nav-item"><button class="nav-link active" data-tab="provinces">Provincias</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="municipalities">Municipios</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="barrios">Barrios</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="types">Tipos de Incidencia</button></li>
  </ul>

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div id="cardTitle">Provincias</div>
      <small class="text-muted" id="counter"></small>
    </div>
    <ul class="list-group list-group-flush" id="listUL">
      <li class="list-group-item text-muted">Cargando...</li>
    </ul>
  </div>
</div>

<!-- Modal editar/crear -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="editForm">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Nuevo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="inpId">
        <input type="hidden" name="entity" id="inpEntity" value="provinces">
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input name="name" id="inpName" class="form-control" required autocomplete="off">
        </div>
        <div class="row g-2" id="parentRow" style="display:none">
          <div class="col-12" id="parentProvince" style="display:none">
            <label class="form-label">Provincia (ID)</label>
            <input class="form-control" name="province_id" id="inpProvinceId" placeholder="Ej: 1">
          </div>
          <div class="col-12" id="parentMunicipality" style="display:none">
            <label class="form-label">Municipio (ID)</label>
            <input class="form-control" name="municipality_id" id="inpMunicipalityId" placeholder="Ej: 10">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
// ===== BASE y API absolutas (sin depender de <base>) =====
const BASE = <?= json_encode($BASE_SIMPLE ?? '/AlertaRD') ?>;
  const API_BASE = BASE + '/api';

  const pill = (id) => id ? `<small class="text-muted me-2">#${id}</small>` : '';


// ===== utilidades =====
function alertBox(kind, msg){
  const box = document.getElementById('alertBox');
  box.className = 'alert alert-' + (kind || 'danger');
  box.textContent = msg || '';
  box.style.display = msg ? '' : 'none';
  if (msg) setTimeout(()=> (box.style.display='none'), 4000);
}
const $ = (q)=>document.querySelector(q);
const $$ = (q)=>Array.from(document.querySelectorAll(q));

async function apiGet(url){
  try{
    const r = await fetch(url, {credentials:'same-origin'});
    if(!r.ok) throw new Error('GET ' + r.status);
    return await r.json();
  }catch(e){ alertBox('danger', 'Error: '+e.message); throw e; }
}
async function apiPost(url, data){
  const headers = {};
  const csrf = document.getElementById('csrf').value;
  let body;
  if (data instanceof FormData){
    if(!data.has('csrf_token')) data.append('csrf_token', csrf);
    body = data;
  } else {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(Object.assign({csrf_token: csrf}, data));
  }
  try{
    const r = await fetch(url, {method:'POST', headers, body, credentials:'same-origin'});
    if(!r.ok) throw new Error('POST ' + r.status);
    return await r.json();
  }catch(e){ alertBox('danger', 'Error: '+e.message); throw e; }
}

// ===== lógica de catálogos =====
let TAB = 'provinces';
let cached = [];

function setActiveTab(tab){
  TAB = tab;
  $('#inpEntity').value = TAB;
  const titles = {'provinces':'Provincias','municipalities':'Municipios','barrios':'Barrios','types':'Tipos de Incidencia'};
  $('#cardTitle').textContent = titles[TAB];
  $('#parentRow').style.display = (TAB==='municipalities' || TAB==='barrios') ? '' : 'none';
  $('#parentProvince').style.display = (TAB==='municipalities') ? '' : 'none';
  $('#parentMunicipality').style.display = (TAB==='barrios') ? '' : 'none';
  loadList();
}

function renderList(rows){
  const ul = $('#listUL');
  const q = $('#search').value.trim().toLowerCase();

  // También permite buscar por ID
  const filt = q
    ? rows.filter(r => (r.name||'').toLowerCase().includes(q) || String(r.id).includes(q))
    : rows;

  $('#counter').textContent = filt.length ? (filt.length + ' registros') : '';

  if (!filt.length){
    ul.innerHTML = '<li class="list-group-item text-muted">Sin registros</li>';
    return;
  }

  ul.innerHTML = '';
  for (const row of filt){
    // Línea del padre (solo cuando aplica)
    const parentLine =
      TAB === 'municipalities' && row.province_id
        ? `<div class="small text-muted">Provincia ${pill(row.province_id)}</div>`
      : TAB === 'barrios' && row.municipality_id
        ? `<div class="small text-muted">Municipio ${pill(row.municipality_id)}</div>`
      : '';

    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `
      <div>
        <div class="fw-semibold">${pill(row.id)}${row.name}</div>
        ${parentLine}
      </div>
      <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-secondary" data-id="${row.id}" data-name="${row.name}" data-action="edit">Editar</button>
        <button class="btn btn-outline-danger"    data-id="${row.id}" data-action="del">Eliminar</button>
      </div>`;
    ul.appendChild(li);
  }
}

async function loadList(){
  $('#listUL').innerHTML = '<li class="list-group-item text-muted">Cargando...</li>';
  try{
    const j = await apiGet(`${API_BASE}/catalog_list.php?entity=${encodeURIComponent(TAB)}`);
    if(!j.ok){ alertBox('danger', j.error || 'Error'); $('#listUL').innerHTML = '<li class="list-group-item text-danger">'+(j.error||'Error')+'</li>'; return; }
    cached = j.data || [];
    renderList(cached);
  }catch(e){
    $('#listUL').innerHTML = '<li class="list-group-item text-danger">'+(e.message||'Error')+'</li>';
  }
}

function openNew(){
  $('#modalTitle').textContent = 'Nuevo';
  $('#inpId').value = '';
  $('#inpName').value = '';
  $('#inpProvinceId').value = '';
  $('#inpMunicipalityId').value = '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
function openEdit(id, name){
  $('#modalTitle').textContent = 'Editar';
  $('#inpId').value = String(id);
  $('#inpName').value = name || '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Eventos UI
document.getElementById('tabs').addEventListener('click', (e)=>{
  const btn = e.target.closest('button[data-tab]'); if(!btn) return;
  document.querySelectorAll('#tabs .nav-link').forEach(x=>x.classList.remove('active'));
  btn.classList.add('active');
  setActiveTab(btn.getAttribute('data-tab'));
});
document.getElementById('btnNew').addEventListener('click', openNew);
document.getElementById('listUL').addEventListener('click', async (e)=>{
  const btn = e.target.closest('button[data-action]'); if(!btn) return;
  const id = btn.getAttribute('data-id');
  if (btn.getAttribute('data-action') === 'edit') {
    openEdit(id, btn.getAttribute('data-name'));
  } else if (btn.getAttribute('data-action') === 'del') {
    if (!confirm('¿Eliminar registro?')) return;
    const fd = new FormData();
    fd.append('entity', TAB);
    fd.append('id', id);
    const j = await apiPost(`${API_BASE}/catalog_delete.php`, fd);
    if (!j.ok) { alertBox('danger', j.error || 'No se pudo eliminar'); return; }
    await loadList();
  }
});
document.getElementById('editForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const j = await apiPost(`${API_BASE}/catalog_save.php`, fd);
  if(!j.ok){ alertBox('danger', j.error || 'No se pudo guardar'); return; }
  bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
  await loadList();
});
document.getElementById('search').addEventListener('input', ()=> renderList(cached));

// start
setActiveTab('provinces');
</script>
<?php require __DIR__ . '/../partials/footer_catalog.php'; ?>
