<?php require __DIR__.'/partials/header.php'; ?>
<h4 class="mb-3">Incidencias</h4>

<form id="filters" class="row g-2 mb-3">
  <div class="col-md-4">
    <input class="form-control" name="q" placeholder="Buscar por título o descripción">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="province_id" id="provinceSelect">
      <option value="">Provincia</option>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="type_id" id="typeSelect">
      <option value="">Tipo</option>
    </select>
  </div>
  <div class="col-md-1"><input type="date" class="form-control" name="date_from"></div>
  <div class="col-md-1"><input type="date" class="form-control" name="date_to"></div>
  <div class="col-md-1 d-grid"><button class="btn btn-primary">Aplicar</button></div>
  <div class="col-md-1 d-grid"><button type="button" id="btnClear" class="btn btn-outline-secondary">Limpiar</button></div>
</form>

<div id="list" class="list-group mb-3"></div>

<nav>
  <ul class="pagination" id="pager"></ul>
</nav>

<script>
let currentPage = 1, currentLimit = 15;

function escapeRegExp(s){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
function hi(txt, term){
  if (!term) return txt;
  const re = new RegExp('(' + escapeRegExp(term) + ')', 'ig');
  return (txt||'').replace(re, '<mark>$1</mark>');
}

function cardHtml(r){
  const searchTerm = (new URLSearchParams(new FormData(document.getElementById('filters'))).get('q') || '').trim();
  const loc = [r.province, r.municipality].filter(Boolean).join(', ');
  const types = (r.types||'').split(', ').filter(Boolean).map(t=>(
    `<span class="badge text-bg-light border me-1">${t}</span>`
  )).join('');
  const rawDesc = (r.description||'');
  const desc = rawDesc.length>180 ? rawDesc.slice(0,180)+'…' : rawDesc;
  const photo = r.photos_count>0 ? `<span class="ms-2 small text-muted">📷 ${r.photos_count}</span>` : '';
  const when = formatDateTime(r.occurrence_at)||'';

  return `
  <a href="/alertard/incident.php?id=${r.id}" class="list-group-item list-group-item-action">
    <div class="d-flex w-100 justify-content-between">
      <h6 class="mb-1">${hi(r.title||'(sin título)', searchTerm)}</h6>
      <small class="text-nowrap">${when}</small>
    </div>
    <div class="mb-1 text-muted small">${loc||'—'}</div>
    <p class="mb-1">${hi(desc||'', searchTerm)}</p>
    <div class="d-flex justify-content-between align-items-center">
      <div>${types||''}</div>
      <div class="small text-muted">${r.latitude && r.longitude ? '🗺️ Con ubicación' : '—'}${photo}</div>
    </div>
  </a>`;
}


async function loadCatalogs() {
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  const t = await apiGet('/alertard/api/catalogs.php?resource=types');
  const ps = document.getElementById('provinceSelect');
  const ts = document.getElementById('typeSelect');
  p.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ps.appendChild(o); });
  t.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ts.appendChild(o); });
}

async function load(page=1){
  currentPage = page;
  const qs = new URLSearchParams(new FormData(document.getElementById('filters')));
  qs.set('page', page); qs.set('limit', currentLimit);
  const res = await apiGet('/alertard/api/incidents_list.php?' + qs.toString());
  const list = document.getElementById('list');
  list.innerHTML = res.data.map(cardHtml).join('') || `<div class="text-muted p-3">No hay resultados.</div>`;
  renderPager(res.total, res.page, res.limit);
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

document.getElementById('filters').addEventListener('submit', (e)=>{ e.preventDefault(); load(1); });
document.getElementById('btnClear').addEventListener('click', ()=>{ document.getElementById('filters').reset(); load(1); });

(async function init(){
  await loadCatalogs();
  await load(1);
})();
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
