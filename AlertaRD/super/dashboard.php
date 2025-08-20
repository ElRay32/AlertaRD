<?php require __DIR__.'/../partials/header.php'; ?>
<h4 class="mb-3">/super - Pendientes</h4>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Incidentes pendientes</div>
      <ul class="list-group list-group-flush" id="pendInc"></ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Correcciones pendientes</div>
      <ul class="list-group list-group-flush" id="pendCorr"></ul>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Unir duplicados (merge)</div>
  <div class="card-body">
    <form class="row g-2" id="mergeForm">
      <div class="col"><input class="form-control" name="parent_id" placeholder="ID principal (parent)"></div>
      <div class="col"><input class="form-control" name="child_id" placeholder="ID duplicado (child)"></div>
      <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Unir</button></div>
    </form>
  </div>
</div>

<script>
async function loadPending() {
  const res = await apiGet('/alertard/api/super_pending.php');
  const inc = document.getElementById('pendInc'); inc.innerHTML='';
  res.incidents.forEach(i=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>#${i.id} · ${i.title} <small class="text-muted">${formatDateTime(i.occurrence_at)}</small></span>
                    <div class="btn-group">
                      <a class="btn btn-sm btn-outline-secondary" href="/alertard/incident.php?id=${i.id}" target="_blank">Ver</a>
                      <button class="btn btn-sm btn-success" onclick="publish(${i.id})">Publicar</button>
                    </div>`;
    inc.appendChild(li);
  });
  const cor = document.getElementById('pendCorr'); cor.innerHTML='';
  res.corrections.forEach(c=>{
    const li = document.createElement('li');
    li.className='list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>Corrección #${c.id} · Incidente ${c.incident_id} <small class="text-muted">${formatDateTime(c.created_at)}</small></span>`;
    cor.appendChild(li);
  });
}
async function publish(id) {
  await apiPost('/alertard/api/super_publish.php', {id});
  await loadPending();
}
document.getElementById('mergeForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  await apiPost('/alertard/api/super_merge.php', fd);
  alert('Merge realizado'); e.target.reset();
});
loadPending();
</script>
<?php require __DIR__.'/../partials/footer.php'; ?>
