<?php require __DIR__.'/../partials/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Entrar como Reportero</h5>
        <p class="text-muted small">Usa tu cuenta corporativa o personal. Tu rol será <strong>reporter</strong>; tus reportes deberán ser validados antes de publicarse.</p>
        <div class="d-grid gap-2">
          <a class="btn btn-danger" href="/alertard/auth/google_start.php">Continuar con Google</a>
          <a class="btn btn-primary" href="/alertard/auth/ms_start.php">Continuar con Microsoft</a>
        </div>
        <hr>
        <p class="small text-muted mb-1">¿Eres Validador/Admin?</p>
        <a class="btn btn-outline-secondary btn-sm" href="/alertard/super/login.php">Entrar a /super (demo)</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../partials/footer.php'; ?>
