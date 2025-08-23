
<?php $title='Incidencias'; require __DIR__.'/partials/header.php'; ?>


<div class="container py-4">
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
    <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit">Aplicar</button></div>
    <div class="col-md-1 d-grid"><button type="button" id="btnClear" class="btn btn-outline-secondary">Limpiar</button></div>
  </form>

  <!-- SOLO UNO -->
  <div class="row g-3" id="results"></div>
  <div id="pager" class="d-flex justify-content-between align-items-center mt-3"></div>
</div>

<script>
let currentPage = 1, currentLimit = 12;

function option(el, value, text){ const o=document.createElement('option'); o.value=value; o.textContent=text; el.appendChild(o); }

async function loadFilters(){
  // Provincias
  const selProv = document.querySelector('select[name="province_id"]');
  selProv.innerHTML = ''; option(selProv,'','Provincia');
  const rp = await apiGet('/api/catalog_list.php?entity=provinces');
  (rp.data||[]).forEach(p => option(selProv, p.id, p.name));

  // Tipos
  const selType = document.querySelector('select[name="type_id"]');
  selType.innerHTML = ''; option(selType,'','Tipo');
  const rt = await apiGet('/api/catalog_list.php?entity=types');
  (rt.data||[]).forEach(t => option(selType, t.id, t.name));
}

function rowCard(r){
  const img = r.photo || (window.BASE_URL + '/assets/img/no-photo.png'); // usa tu placeholder si existe
  const loc = [r.province, r.municipality].filter(Boolean).join(', ');
  const date = (r.occurrence_at||'').replace('T',' ').slice(0,16);
  return `
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <img src="${img}" class="card-img-top" style="height:160px;object-fit:cover" onerror="this.style.display='none'">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title mb-1">${r.title||'(sin título)'}</h6>
          <div class="text-muted small mb-2">${loc||'(sin ubicación)'} · ${date}</div>
          <div class="small mb-2">${r.types || '<span class="text-muted">(sin tipo)</span>'}</div>
          <p class="card-text flex-grow-1">${(r.description||'').slice(0,120)}${(r.description||'').length>120?'…':''}</p>
          <div class="mt-2">
            <a class="btn btn-sm btn-outline-primary" href="${(window.BASE_URL||'') + '/incident.php?id=' + r.id}">Ver detalle</a>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderPager(total, page, limit){
  const pages = Math.max(1, Math.ceil(total/limit));
  const prev = `<button class="btn btn-sm btn-outline-secondary" ${page<=1?'disabled':''} onclick="load(${page-1})">«</button>`;
  const next = `<button class="btn btn-sm btn-outline-secondary" ${page>=pages?'disabled':''} onclick="load(${page+1})">»</button>`;
  document.getElementById('pager').innerHTML =
    `<div>Total: ${total}</div><div class="d-flex gap-2 align-items-center">${prev}<span>Página ${page}/${pages}</span>${next}</div>`;
}

async function load(page=1){
  currentPage = page;
  const fd = new FormData(document.querySelector('form#filters') || document.getElementById('filters') || document.forms[0]);
  const qs = new URLSearchParams();
  for (const [k,v] of fd.entries()){
    if (v!==null && String(v).trim()!=='') qs.set(k, v);
  }
  qs.set('page', page); 
  qs.set('limit', currentLimit);

  qs.set('status','published');

  const res = await apiGet('/api/incidents_list.php?'+qs.toString());
  const grid = document.getElementById('results');
  grid.innerHTML = (res.data||[]).map(rowCard).join('') || `<div class="col-12 text-muted">No se encontraron incidencias.</div>`;
  renderPager(res.total||0, res.page||1, res.limit||currentLimit);
}

document.addEventListener('DOMContentLoaded', async ()=>{
  try {
    await loadFilters(); // si falla, seguimos
  } catch (e) {
    console.warn('No se pudieron cargar filtros (continuo):', e);
  }

  await load(1); // SIEMPRE cargar la lista

  const form = document.getElementById('filters');
  form.addEventListener('submit', (e)=>{ e.preventDefault(); load(1); });

  const btnClear = document.getElementById('btnClear');
  if (btnClear) btnClear.addEventListener('click', ()=>{ form.reset(); load(1); });
});

</script>

<?php require __DIR__.'/partials/footer.php'; ?>
