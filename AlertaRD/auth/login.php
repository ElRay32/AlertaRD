<?php require __DIR__.'/../partials/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Entrar como Reportero</h5>
        <form method="post" action="/alertard/auth/email_start.php" class="row g-2">
          <div class="col-12">
            <input type="email" class="form-control" name="email" placeholder="Tu correo (Gmail u Outlook)" required>
            <div class="form-text">Sólo se aceptan dominios de Google/Microsoft (gmail.com, outlook.com, etc.).</div>
          </div>
          <div class="col-12 d-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="email" name="email" class="form-control" required>
            <button class="btn btn-primary" type="submit">Enviar código</button>
          </div>
        </form>
        <hr>
        <p class="small text-muted mb-1">¿Eres Validador/Admin?</p>
        <a class="btn btn-outline-secondary btn-sm" href="/alertard/super/login.php">Entrar a /super</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../partials/footer.php'; ?>
