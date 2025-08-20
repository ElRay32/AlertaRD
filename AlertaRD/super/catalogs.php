<?php require __DIR__.'/../partials/header.php'; ?>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<h4 class="mb-3">Catálogos</h4>

<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
  <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-provinces" type="button">Provincias</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-munis" type="button">Municipios</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-barrios" type="button">Barrios</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-types" type="button">Tipos de Incidencia</button></li>
</ul>

<div class="tab-content">
  <!-- Provincias -->
  <div class="tab-pane fade show active" id="tab-provinces" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="m-0">Provincias</h6>
      <button class="btn btn-sm btn-primary" onclick="openProvModal()">Nueva</button>
    </div>
    <ul class="list-group" id="provList"></ul>
  </div>

  <!-- Municipios -->
  <div class="tab-pane fade" id="tab-munis" role="tabpanel">
    <div class="row g-2 align-items-end mb-2">
      <div class="col-md-6">
        <label class="form-label">Provincia</label>
        <select id="munProv" class="form-select"></select>
      </div>
      <div class="col-md-6 text-end">
        <button class="btn btn-sm btn-primary" onclick="openMunModal()">Nuevo</button>
      </div>
    </div>
    <ul class="list-group" id="munList"></ul>
  </div>

  <!-- Barrios -->
  <div class="tab-pane fade" id="tab-barrios" role="tabpanel">
    <div class="row g-2 align-items-end mb-2">
      <div class="col-md-6">
        <label class="form-label">Provincia</label>
        <select id="barProv" class="form-select"></select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Municipio</label>
        <select id="barMun" class="form-select"></select>
      </div>
      <div class="col-12 text-end">
        <button class="btn btn-sm btn-primary" onclick="openBarrioModal()">Nuevo</button>
      </div>
    </div>
    <ul class="list-group" id="barList"></ul>
  </div>

  <!-- Tipos -->
  <div class="tab-pane fade" id="tab-types" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="m-0">Tipos de Incidencia</h6>
      <button class="btn btn-sm btn-primary" onclick="openTypeModal()">Nuevo</button>
    </div>
    <ul class="list-group" id="typeList"></ul>
  </div>
</div>

<!-- Modal genérico -->
<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="catForm">
      <div class="modal-header">
        <h5 class="modal-title" id="catTitle">Nuevo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="catBody"></div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
// Utilidades
async function loadProvinces(selectEl) {
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  if (selectEl) {
    selectEl.innerHTML = '';
    p.forEach(x => { let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; selectEl.appendChild(o); });
  }
  return p;
}
async function loadMunicipalities(province_id, selectEl) {
  const url = '/alertard/api/catalogs.php?resource=municipalities&province_id=' + province_id;
  const m = province_id ? await apiGet(url) : [];
  if (selectEl) {
    selectEl.innerHTML = '';
    m.forEach(x => { let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; selectEl.appendChild(o); });
  }
  return m;
}

// ----- Provincias -----
async function renderProvinces() {
  const list = document.getElementById('provList');
  list.innerHTML = '';
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  p.forEach(x=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>${x.name}</span>
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-secondary" onclick="openProvModal(${x.id}, '${x.name.replace(/'/g,"&#39;")}')">Editar</button>
        <button class="btn btn-sm btn-outline-danger" onclick="delItem('provinces', ${x.id}, '${x.name.replace(/'/g,"&#39;")}')">Eliminar</button>
      </div>`;
    list.appendChild(li);
  });
}
function openProvModal(id=null, name='') {
  document.getElementById('catTitle').textContent = id? 'Editar Provincia' : 'Nueva Provincia';
  document.getElementById('catBody').innerHTML = `
    <input type="hidden" name="resource" value="provinces">
    ${id?`<input type="hidden" name="id" value="${id}">`:''}
    <label class="form-label">Nombre</label>
    <input class="form-control" name="name" value="${name}">
  `;
  new bootstrap.Modal(document.getElementById('catModal')).show();
}

// ----- Municipios -----
async function renderMunicipalities() {
  const provSel = document.getElementById('munProv');
  const list = document.getElementById('munList');
  list.innerHTML='';
  const pid = provSel.value;
  if (!pid) { list.innerHTML = '<li class="list-group-item text-muted">Selecciona una provincia</li>'; return; }
  const m = await apiGet('/alertard/api/catalogs.php?resource=municipalities&province_id=' + pid);
  m.forEach(x=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>${x.name}</span>
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-secondary" onclick="openMunModal(${x.id}, '${x.name.replace(/'/g,"&#39;")}', ${pid})">Editar</button>
        <button class="btn btn-sm btn-outline-danger" onclick="delItem('municipalities', ${x.id}, '${x.name.replace(/'/g,"&#39;")}')">Eliminar</button>
      </div>`;
    list.appendChild(li);
  });
}
async function openMunModal(id=null, name='', provId=null) {
  const provs = await loadProvinces(null);
  const provOptions = provs.map(p=>`<option value="${p.id}" ${provId==p.id?'selected':''}>${p.name}</option>`).join('');
  document.getElementById('catTitle').textContent = id? 'Editar Municipio' : 'Nuevo Municipio';
  document.getElementById('catBody').innerHTML = `
    <input type="hidden" name="resource" value="municipalities">
    ${id?`<input type="hidden" name="id" value="${id}">`:''}
    <label class="form-label">Provincia</label>
    <select class="form-select mb-2" name="province_id">${provOptions}</select>
    <label class="form-label">Nombre</label>
    <input class="form-control" name="name" value="${name}">
  `;
  new bootstrap.Modal(document.getElementById('catModal')).show();
}

// ----- Barrios -----
async function renderBarrios() {
  const provSel = document.getElementById('barProv');
  const munSel  = document.getElementById('barMun');
  const list = document.getElementById('barList');
  list.innerHTML='';
  const pid = provSel.value;
  const mid = munSel.value;
  if (!pid) { list.innerHTML = '<li class="list-group-item text-muted">Selecciona provincia</li>'; return; }
  if (!mid) { list.innerHTML = '<li class="list-group-item text-muted">Selecciona municipio</li>'; return; }
  const b = await apiGet('/alertard/api/catalogs.php?resource=barrios&municipality_id=' + mid);
  b.forEach(x=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>${x.name}</span>
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-secondary" onclick="openBarrioModal(${x.id}, '${x.name.replace(/'/g,"&#39;")}', ${mid})">Editar</button>
        <button class="btn btn-sm btn-outline-danger" onclick="delItem('barrios', ${x.id}, '${x.name.replace(/'/g,"&#39;")}')">Eliminar</button>
      </div>`;
    list.appendChild(li);
  });
}
async function openBarrioModal(id=null, name='', munId=null) {
  // Selección de provincia y municipio dentro del modal
  const provs = await loadProvinces(null);
  const provOptions = provs.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
  document.getElementById('catTitle').textContent = id? 'Editar Barrio' : 'Nuevo Barrio';
  document.getElementById('catBody').innerHTML = `
    <input type="hidden" name="resource" value="barrios">
    ${id?`<input type="hidden" name="id" value="${id}">`:''}
    <div class="mb-2">
      <label class="form-label">Provincia</label>
      <select class="form-select" id="modalProv">${provOptions}</select>
    </div>
    <div class="mb-2">
      <label class="form-label">Municipio</label>
      <select class="form-select" id="modalMun"></select>
    </div>
    <label class="form-label">Nombre</label>
    <input class="form-control" name="name" value="${name}">
  `;
  const modal = new bootstrap.Modal(document.getElementById('catModal'));
  modal.show();

  const modalProv = document.getElementById('modalProv');
  const modalMun  = document.getElementById('modalMun');

  // Si nos pasan munId, seleccionar provincia correspondiente
  if (munId) {
    // Buscar provincia de ese municipio
    // Cargamos municipios de cada provincia hasta encontrar (simple para demo)
    for (const p of provs) {
      const list = await loadMunicipalities(p.id, null);
      if (list.find(x=>x.id==munId)) {
        modalProv.value = p.id;
        const ms = await loadMunicipalities(p.id, modalMun);
        modalMun.value = munId;
        break;
      }
    }
  } else {
    // default: primera provincia
    modalProv.dispatchEvent(new Event('change'));
  }

  modalProv.addEventListener('change', async ()=>{
    await loadMunicipalities(modalProv.value, modalMun);
  });
}

// ----- Tipos -----
async function renderTypes() {
  const list = document.getElementById('typeList');
  list.innerHTML = '';
  const t = await apiGet('/alertard/api/catalogs.php?resource=types');
  t.forEach(x=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>${x.name}</span>
      <div class="btn-group">
        <button class="btn btn-sm btn-outline-secondary" onclick="openTypeModal(${x.id}, '${x.name.replace(/'/g,"&#39;")}')">Editar</button>
        <button class="btn btn-sm btn-outline-danger" onclick="delItem('types', ${x.id}, '${x.name.replace(/'/g,"&#39;")}')">Eliminar</button>
      </div>`;
    list.appendChild(li);
  });
}
function openTypeModal(id=null, name='') {
  document.getElementById('catTitle').textContent = id? 'Editar Tipo' : 'Nuevo Tipo';
  document.getElementById('catBody').innerHTML = `
    <input type="hidden" name="resource" value="types">
    ${id?`<input type="hidden" name="id" value="${id}">`:''}
    <label class="form-label">Nombre</label>
    <input class="form-control" name="name" value="${name}">
  `;
  new bootstrap.Modal(document.getElementById('catModal')).show();
}

// ----- Guardar / Eliminar -----
document.getElementById('catForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await apiPost('/alertard/api/catalog_save.php', fd);
  bootstrap.Modal.getInstance(document.getElementById('catModal')).hide();

  // refrescar la pestaña activa
  if (fd.get('resource')==='provinces') await renderProvinces();
  if (fd.get('resource')==='municipalities') await renderMunicipalities();
  if (fd.get('resource')==='barrios') await renderBarrios();
  if (fd.get('resource')==='types') await renderTypes();
});

async function delItem(resource, id, name) {
  if (!confirm(`¿Eliminar "${name}"?`)) return;
  try {
    await apiPost('/alertard/api/catalog_delete.php', {resource, id});
  } catch (e) {
    try {
      const err = JSON.parse(e.message.split(' ').slice(-1)[0]);
      if (err.error==='in_use') { alert('No se puede eliminar: está en uso.'); return; }
    } catch(_) {}
    alert('Error al eliminar.');
  }
  if (resource==='provinces') await renderProvinces();
  if (resource==='municipalities') await renderMunicipalities();
  if (resource==='barrios') await renderBarrios();
  if (resource==='types') await renderTypes();
}

// ----- Inicialización -----
(async function init(){
  // Provincias
  await renderProvinces();

  // Municipios
  const munProv = document.getElementById('munProv');
  await loadProvinces(munProv);
  munProv.addEventListener('change', renderMunicipalities);
  await renderMunicipalities();

  // Barrios
  const barProv = document.getElementById('barProv');
  const barMun  = document.getElementById('barMun');
  await loadProvinces(barProv);
  barProv.addEventListener('change', async ()=>{
    await loadMunicipalities(barProv.value, barMun);
    await renderBarrios();
  });
  barMun.addEventListener('change', renderBarrios);
  // arranque en vacío
  await renderBarrios();

  // Tipos
  await renderTypes();
})();
</script>

<?php require __DIR__.'/../partials/footer.php'; ?>
