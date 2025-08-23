<?php
// /AlertaRD/super/catalogs_clean.php
require_once __DIR__.'/../api/helpers.php';
start_session_safe();
require_role(['validator','admin']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogos (clean)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Catálogos (clean)</h4>
    <input type="hidden" id="csrf" value="<?= csrf_token() ?>">
    <button class="btn btn-primary" id="btnNew">Nuevo</button>
  </div>
  <ul class="nav nav-tabs mb-3" id="tabs">
    <li class="nav-item"><button class="nav-link active" data-tab="provinces">Provincias</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="municipalities">Municipios</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="barrios">Barrios</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="types">Tipos de Incidencia</button></li>
  </ul>
  <div class="card">
    <div class="card-header" id="cardTitle">Provincias</div>
    <ul class="list-group list-group-flush" id="listUL">
      <li class="list-group-item text-muted">Cargando...</li>
    </ul>
  </div>
</div>

<!-- Modal -->
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
            <label class="form-label">Provincia</label>
            <input class="form-control" name="province_id" id="inpProvinceId" placeholder="(opcional id)">
          </div>
          <div class="col-12" id="parentMunicipality" style="display:none">
            <label class="form-label">Municipio</label>
            <input class="form-control" name="municipality_id" id="inpMunicipalityId" placeholder="(opcional id)">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API = '../api'; // rutas relativas desde /super/
let TAB = 'provinces';

const $ = (q)=>document.querySelector(q);
function setActiveTab(tab){
  TAB = tab;
  $('#inpEntity').value = TAB;
  $('#cardTitle').textContent = {'provinces':'Provincias','municipalities':'Municipios','barrios':'Barrios','types':'Tipos de Incidencia'}[TAB];
  // Mostrar inputs de relación solo cuando aplique
  $('#parentRow').style.display = (TAB==='municipalities' || TAB==='barrios') ? '' : 'none';
  $('#parentProvince').style.display = (TAB==='municipalities') ? '' : 'none';
  $('#parentMunicipality').style.display = (TAB==='barrios') ? '' : 'none';
  loadList();
}

async function apiGet(url){
  const r = await fetch(url, {credentials:'same-origin'});
  return r.json();
}
async function apiPost(url, data){
  const headers = {};
  if (!(data instanceof FormData)){
    headers['Content-Type'] = 'application/json';
    data = JSON.stringify(Object.assign({csrf_token: $('#csrf').value}, data));
  } else {
    if (!data.has('csrf_token')) data.append('csrf_token', $('#csrf').value);
  }
  const r = await fetch(url, {method:'POST', headers, body: data, credentials:'same-origin'});
  return r.json();
}

async function loadList(){
  const ul = $('#listUL');
  ul.innerHTML = '<li class="list-group-item text-muted">Cargando...</li>';
  try{
    const j = await apiGet(`${API}/catalog_list.php?entity=${encodeURIComponent(TAB)}`);
    if(!j.ok) throw new Error(j.error||'Error');
    if(!j.data?.length){ ul.innerHTML = '<li class="list-group-item text-muted">Sin registros</li>'; return; }
    ul.innerHTML = '';
    j.data.forEach(row=>{
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      li.innerHTML = `
        <span>${row.name}</span>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary" data-id="${row.id}" data-name="${row.name}" data-action="edit">Editar</button>
          <button class="btn btn-outline-danger" data-id="${row.id}" data-action="del">Eliminar</button>
        </div>`;
      ul.appendChild(li);
    });
  }catch(e){
    ul.innerHTML = `<li class="list-group-item text-danger">${e.message}</li>`;
  }
}

function openNew(){
  $('#modalTitle').textContent = 'Nuevo';
  $('#inpId').value = '';
  $('#inpName').value = '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function openEdit(id, name){
  $('#modalTitle').textContent = 'Editar';
  $('#inpId').value = String(id);
  $('#inpName').value = name || '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

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
    const j = await apiPost(`${API}/catalog_delete.php`, fd);
    if (!j.ok) return alert(j.error||'No se pudo eliminar');
    await loadList();
  }
});
document.getElementById('editForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const j = await apiPost(`${API}/catalog_save.php`, fd);
  if(!j.ok){ console.error(j); return alert(j.error||'No se pudo guardar'); }
  bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
  await loadList();
});

setActiveTab('provinces');
</script>
</body>
</html>
