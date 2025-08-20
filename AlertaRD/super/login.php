<?php require __DIR__.'/../partials/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Entrar (modo demo)</h5>
        <p class="text-muted small">Selecciona un rol simulado para pruebas locales:</p>
        <div class="d-grid gap-2">
          <a class="btn btn-primary" href="/alertard/auth/mock_login.php?role=reporter">Entrar como Reportero</a>
          <a class="btn btn-secondary" href="/alertard/auth/mock_login.php?role=validator">Entrar como Validador</a>
          <a class="btn btn-outline-dark" href="/alertard/auth/mock_login.php?role=admin">Entrar como Admin</a>
        </div>
        <hr>
        <p class="small text-muted">En producción, reemplaza esto por OAuth Google/Microsoft y login local para /super.</p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../partials/footer.php'; ?>
