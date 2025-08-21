<?php require __DIR__.'/../partials/header.php'; ?>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<h4 class="mb-3">Estadísticas (últimos 30 días)</h4>
<canvas id="chartTypes" height="120"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(async function(){
  const res = await apiGet('/alertard/api/super_stats_types.php');
  const labels = res.data.map(r=>r.type_name);
  const values = res.data.map(r=>r.total);

  const ctx = document.getElementById('chartTypes').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Incidencias publicadas', data: values }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true, ticks: { precision:0 } } }
    }
  });
})();
</script>
<?php require __DIR__.'/../partials/footer.php'; ?>
