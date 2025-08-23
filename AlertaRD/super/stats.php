<?php require __DIR__.'/../partials/header.php'; ?>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<h4 class="mb-3">Estadísticas</h4>

<form id="filters" class="row g-2 mb-3">
  <div class="col-auto">
    <label class="form-label">Desde</label>
    <input type="date" class="form-control" name="date_from">
  </div>
  <div class="col-auto">
    <label class="form-label">Hasta</label>
    <input type="date" class="form-control" name="date_to">
  </div>
  <div class="col-auto">
    <label class="form-label">Estatus</label>
    <div class="d-flex gap-2">
      <label class="form-check">
        <input class="form-check-input" type="checkbox" name="status[]" value="published" checked> Publicadas
      </label>
      <label class="form-check">
        <input class="form-check-input" type="checkbox" name="status[]" value="pending"> Pendientes
      </label>
      <label class="form-check">
        <input class="form-check-input" type="checkbox" name="status[]" value="rejected"> Rechazadas
      </label>
      <label class="form-check">
        <input class="form-check-input" type="checkbox" name="status[]" value="deleted"> Eliminadas
      </label>
    </div>
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary" type="submit">Aplicar</button>
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-outline-secondary" type="button" id="btnLast30">Últimos 30 días</button>
  </div>
</form>

<!-- Resumen -->
<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Publicadas</div>
      <div class="fs-3" id="statPublished">—</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Pendientes</div>
      <div class="fs-3" id="statPending">—</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Rechazadas</div>
      <div class="fs-3" id="statRejected">—</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Eliminadas</div>
      <div class="fs-3" id="statDeleted">—</div>
    </div></div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card shadow-sm"><div class="card-body">
      <h6 class="card-title">Por tipo</h6>
      <canvas id="chartTypes" height="140"></canvas>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm"><div class="card-body">
      <h6 class="card-title">Tendencia diaria</h6>
      <canvas id="chartDaily" height="140"></canvas>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm"><div class="card-body">
      <h6 class="card-title">Top provincias</h6>
      <canvas id="chartProv" height="140"></canvas>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm"><div class="card-body">
      <h6 class="card-title">Por estatus</h6>
      <canvas id="chartStatus" height="140"></canvas>
    </div></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let chartTypes, chartDaily, chartProv, chartStatus;

function getSelectedStatuses(){
  return [...document.querySelectorAll('input[name="status[]"]:checked')].map(el=>el.value);
}
function getDateRange(){
  return {
    from: document.querySelector('input[name="date_from"]').value,
    to:   document.querySelector('input[name="date_to"]').value
  };
}
function setLast30(){
  const to = new Date(); const from = new Date(); from.setDate(to.getDate()-29);
  document.querySelector('input[name="date_from"]').value = from.toISOString().slice(0,10);
  document.querySelector('input[name="date_to"]').value   = to.toISOString().slice(0,10);
}
function ensureChartsDestroyed(){ [chartTypes, chartDaily, chartProv, chartStatus].forEach(ch=>{ if(ch) ch.destroy(); }); }
function baseQS(){
  const {from,to} = getDateRange();
  const p = new URLSearchParams();
  if (from) p.set('date_from', from);
  if (to)   p.set('date_to',   to);
  return p.toString();
}
function dayLabelsFromRange(from, to){
  let start = from ? new Date(from+'T00:00:00') : new Date(Date.now()-29*86400000);
  let end   = to   ? new Date(to  +'T00:00:00') : new Date();
  start.setHours(0,0,0,0); end.setHours(0,0,0,0);
  const labels=[];
  for (let d=new Date(start); d<=end; d.setDate(d.getDate()+1)) labels.push(d.toISOString().slice(0,10));
  return labels;
}

async function loadStats(){
  const statuses = getSelectedStatuses();
  if (!statuses.length){ alert('Selecciona al menos un estatus'); return; }

  const qs = baseQS();

  // 1) Resumen por estatus: pide cada estatus por separado y los combinamos.
  const statusRows = await Promise.all(statuses.map(async st=>{
    const p = new URLSearchParams(qs); p.append('status', st);
    const r = await apiGet('/api/super_stats_status.php?' + p.toString());
    // r.data puede traer solo 1 fila (ese estatus) o nada
    const total = (r.data||[]).reduce((a,x)=> a + Number(x.total||0), 0);
    return { status: st, total };
  }));
  const statusMap = Object.fromEntries(statusRows.map(x=>[x.status, x.total]));
  document.getElementById('statPublished').textContent = statusMap.published || 0;
  document.getElementById('statPending').textContent   = statusMap.pending   || 0;
  document.getElementById('statRejected').textContent  = statusMap.rejected  || 0;
  document.getElementById('statDeleted').textContent   = statusMap.deleted   || 0;

  // 2) Series por estatus (para comparar)
  const perStatusDaily = await Promise.all(statuses.map(async st=>{
    const p = new URLSearchParams(qs); p.append('status', st);
    const r = await apiGet('/api/super_stats_daily.php?' + p.toString());
    return { status: st, data: r.data||[] };
  }));
  const perStatusTypes = await Promise.all(statuses.map(async st=>{
    const p = new URLSearchParams(qs); p.append('status', st);
    const r = await apiGet('/api/super_stats_types.php?' + p.toString());
    return { status: st, data: r.data||[] }; // [{type_name,total}]
  }));
  const perStatusProv = await Promise.all(statuses.map(async st=>{
    const p = new URLSearchParams(qs); p.append('status', st);
    const r = await apiGet('/api/super_stats_top_provinces.php?' + p.toString());
    return { status: st, data: r.data||[] }; // [{province_name,total}]
  }));

  // 3) Pintar
  ensureChartsDestroyed();

  // Tendencia diaria (multi-línea)
  const labelsDaily = dayLabelsFromRange(
    document.querySelector('input[name="date_from"]').value,
    document.querySelector('input[name="date_to"]').value
  );
  const dailyDatasets = perStatusDaily.map(s=>{
    const map = new Map(s.data.map(r=>[String(r.d), Number(r.total)]));
    return { label: s.status, data: labelsDaily.map(d=>map.get(d)||0), tension: 0.2 };
  });
  chartDaily = new Chart(document.getElementById('chartDaily'), {
    type:'line',
    data:{ labels: labelsDaily, datasets: dailyDatasets },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
  });

  // Por tipo (barras agrupadas)
  const typeLabels = Array.from(new Set(perStatusTypes.flatMap(s=>(s.data||[]).map(r=>r.type_name))));
  const typeDatasets = perStatusTypes.map(s=>({
    label: s.status,
    data: typeLabels.map(name=>{
      const row = (s.data||[]).find(x=>x.type_name===name);
      return row ? Number(row.total) : 0;
    })
  }));
  chartTypes = new Chart(document.getElementById('chartTypes'), {
    type:'bar',
    data:{ labels: typeLabels, datasets: typeDatasets },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
  });

  // Top provincias (barras horizontales agrupadas)
  const provLabels = Array.from(new Set(perStatusProv.flatMap(s=>(s.data||[]).map(r=>r.province_name))));
  const provDatasets = perStatusProv.map(s=>({
    label: s.status,
    data: provLabels.map(name=>{
      const row = (s.data||[]).find(x=>x.province_name===name);
      return row ? Number(row.total) : 0;
    })
  }));
  chartProv = new Chart(document.getElementById('chartProv'), {
    type:'bar',
    data:{ labels: provLabels, datasets: provDatasets },
    options:{ indexAxis:'y', responsive:true, scales:{ x:{ beginAtZero:true, ticks:{ precision:0 } } } }
  });

  // Doughnut (solo estatus seleccionados)
  chartStatus = new Chart(document.getElementById('chartStatus'), {
    type:'doughnut',
    data:{ labels: statusRows.map(x=>x.status), datasets: [{ data: statusRows.map(x=>x.total) }] },
    options:{ responsive:true }
  });
}

// Eventos
document.getElementById('filters').addEventListener('submit', e=>{ e.preventDefault(); loadStats(); });
document.getElementById('btnLast30').addEventListener('click', ()=>{ setLast30(); loadStats(); });
document.addEventListener('DOMContentLoaded', ()=>{ setLast30(); loadStats(); });

</script>


<?php require __DIR__.'/../partials/footer.php'; ?>
