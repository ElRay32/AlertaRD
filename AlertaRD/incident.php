<?php require __DIR__.'/partials/header.php';
$id = (int)($_GET['id'] ?? 0);
?>
<div id="content">Cargando...</div>
<script>
(async function(){
  const id = <?php echo (int)$_GET['id']; ?>;
  const data = await apiGet('/alertard/api/incident_detail.php?id=' + id);
  const i = data.incident;
  const types = (data.types||[]).map(t=>`<span class="badge text-bg-light border me-1">${t.name}</span>`).join('');
  const photos = (data.photos||[]).map(p=>`<img src="${p.path_or_url}" class="img-fluid rounded me-2 mb-2" style="max-height:150px">`).join('');
  document.getElementById('content').innerHTML = `
    <h4>${i.title}</h4>
    <div class="text-muted small mb-2">${i.province||''}${i.municipality?', '+i.municipality:''} · ${formatDateTime(i.occurrence_at)} · <span class="badge text-bg-${i.status==='published'?'success':(i.status==='pending'?'warning':'secondary')}">${i.status}</span></div>
    <div class="mb-2">${types}</div>
    <p>${i.description||''}</p>
    <div>${photos}</div>
    <hr>
    <h6>Comentarios</h6>
    <div id="comments"></div>
    <form id="commentForm" class="mt-2">
      <input type="hidden" name="incident_id" value="${i.id}">
      <textarea class="form-control mb-2" name="content" placeholder="Escribe un comentario"></textarea>
      <button class="btn btn-sm btn-primary" type="submit">Comentar</button>
    </form>
    <hr>
    <h6>Proponer corrección</h6>
    <form id="corrForm" class="row g-2">
      <input type="hidden" name="incident_id" value="${i.id}">
      <div class="col-md-2"><input class="form-control" type="number" min="0" name="new_deaths" placeholder="Muertos"></div>
      <div class="col-md-2"><input class="form-control" type="number" min="0" name="new_injuries" placeholder="Heridos"></div>
      <div class="col-md-3"><input class="form-control" type="number" step="0.000001" name="new_latitude" placeholder="Latitud"></div>
      <div class="col-md-3"><input class="form-control" type="number" step="0.000001" name="new_longitude" placeholder="Longitud"></div>
      <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="new_loss_estimate_rd" placeholder="Pérdida RD$"></div>
      <div class="col-12"><input class="form-control" name="note" placeholder="Nota"></div>
      <div class="col-12"><button class="btn btn-sm btn-outline-secondary" type="submit">Enviar corrección</button></div>
    </form>
  `;
  function renderComments() {
    const c = (data.comments||[]).map(c=>`<div class="mb-2"><strong>${c.name}</strong> <small class="text-muted">${formatDateTime(c.created_at)}</small><br>${c.content}</div>`).join('') || '<em class="text-muted">Sin comentarios</em>';
    document.getElementById('comments').innerHTML = c;
  }
  renderComments();
  document.getElementById('commentForm').addEventListener('submit', async (e)=>{
    e.preventDefault(); const fd = new FormData(e.target);
    await apiPost('/alertard/api/comment_add.php', fd);
    window.location.reload();
  });
  document.getElementById('corrForm').addEventListener('submit', async (e)=>{
    e.preventDefault(); const fd = new FormData(e.target);
    await apiPost('/alertard/api/correction_add.php', fd);
    alert('Corrección enviada para revisión.');
  });
})();
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
