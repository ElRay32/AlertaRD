<?php require __DIR__.'/partials/header.php'; ?>
<h4 class="mb-3">Reportar Incidencia</h4>
<?php if (($role ?? 'guest')==='guest'): ?>
<div class="alert alert-warning">Debes iniciar sesión (demo) para reportar. <a href="/alertard/super/login.php">Entrar</a></div>
<?php endif; ?>
<form class="row g-3" id="reportForm" enctype="multipart/form-data">
  <div class="col-md-6"><label class="form-label">Título</label><input name="title" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Fecha/Hora</label><input type="datetime-local" name="occurrence_at" class="form-control" value="<?php echo date('Y-m-d\\TH:i'); ?>"></div>
  <div class="col-12"><label class="form-label">Descripción</label><textarea name="description" class="form-control"></textarea></div>

  <div class="col-md-4"><label class="form-label">Provincia</label><select name="province_id" id="province" class="form-select"><option value="">—</option></select></div>
  <div class="col-md-4"><label class="form-label">Municipio</label><select name="municipality_id" id="municipality" class="form-select"><option value="">—</option></select></div>
  <div class="col-md-4"><label class="form-label">Barrio</label><select name="barrio_id" id="barrio" class="form-select"><option value="">—</option></select></div>

  <div class="col-md-3"><label class="form-label">Latitud</label><input type="number" step="0.000001" name="latitude" class="form-control"></div>
  <div class="col-md-3"><label class="form-label">Longitud</label><input type="number" step="0.000001" name="longitude" class="form-control"></div>
  <div class="col-md-2"><label class="form-label">Muertos</label><input type="number" min="0" name="deaths" class="form-control" value="0"></div>
  <div class="col-md-2"><label class="form-label">Heridos</label><input type="number" min="0" name="injuries" class="form-control" value="0"></div>
  <div class="col-md-2"><label class="form-label">Pérdida RD$</label><input type="number" step="0.01" name="loss_estimate_rd" class="form-control"></div>

  <div class="col-md-6">
    <label class="form-label">Tipos</label>
    <select name="type_ids[]" id="types" class="form-select" multiple></select>
    <div class="form-text">Ctrl/Cmd + click para multi-selección</div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Fotos</label>
    <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
  </div>

  <div class="col-12">
    <label class="form-label">Links a redes (uno por línea)</label>
    <textarea name="social_links" class="form-control" rows="3"></textarea>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">Enviar reporte</button>
  </div>
</form>

<script>
async function fillCat() {
  const p = await apiGet('/alertard/api/catalogs.php?resource=provinces');
  const t = await apiGet('/alertard/api/catalogs.php?resource=types');
  const ps = document.getElementById('province');
  p.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ps.appendChild(o); });
  const ts = document.getElementById('types');
  t.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; ts.appendChild(o); });
  ps.addEventListener('change', async ()=>{
    const mid = document.getElementById('municipality');
    const bid = document.getElementById('barrio');
    mid.innerHTML='<option value="">—</option>'; bid.innerHTML='<option value="">—</option>';
    if (!ps.value) return;
    const ms = await apiGet('/alertard/api/catalogs.php?resource=municipalities&province_id=' + ps.value);
    ms.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; mid.appendChild(o); });
    mid.addEventListener('change', async ()=>{
      bid.innerHTML='<option value="">—</option>';
      if (!mid.value) return;
      const bs = await apiGet('/alertard/api/catalogs.php?resource=barrios&municipality_id=' + mid.value);
      bs.forEach(x=>{ let o=document.createElement('option'); o.value=x.id; o.textContent=x.name; bid.appendChild(o); });
    }, {once:true});
  });
}
document.getElementById('reportForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await apiPost('/alertard/api/report_add.php', fd);
  window.location = '/alertard/incident.php?id=' + res.id;
});
fillCat();
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
