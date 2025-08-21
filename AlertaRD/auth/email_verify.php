<?php
require __DIR__.'/../api/db.php';
require __DIR__.'/email_lib.php';
require __DIR__.'/../api/helpers.php';
require_csrf();

// === BLOQUE DE VERIFICACIÓN OTP (REEMPLAZA TU LÓGICA ACTUAL) ===
$email = trim($_POST['email'] ?? (body_json()['email'] ?? ''));
$code  = trim($_POST['code']  ?? (body_json()['code']  ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $code==='') {
  echo json_encode(['ok'=>false,'error'=>'bad_request']); exit;
}

// Trae el token más reciente NO usado y NO vencido
$sql = "SELECT * FROM login_tokens
        WHERE email=? AND used=0 AND expires_at > NOW()
        ORDER BY created_at DESC LIMIT 1";
$st = dbx()->prepare($sql);
$st->execute([$email]);
$tok = $st->fetch();

if (!$tok) {
  echo json_encode(['ok'=>false,'error'=>'invalid_or_expired']); exit;
}

// Demasiados intentos con este token
if (otp_blocked($tok)) {
  echo json_encode(['ok'=>false,'error'=>'too_many_attempts']); exit;
}

// Compara el código (texto plano; si lo guardas hasheado, adapta esta línea)
if (!hash_equals($tok['code'], $code)) {
  otp_register_attempt($tok['id']); // suma intento y marca hora
  echo json_encode(['ok'=>false,'error'=>'wrong_code','attempts'=>((int)$tok['verify_attempts']+1)]); exit;
}

// Éxito: marca token usado
dbx()->prepare("UPDATE login_tokens SET used=1, last_attempt_at=NOW() WHERE id=?")->execute([$tok['id']]);


// crea/actualiza usuario reporter
      $user_id = upsert_reporter_email($email);

      // inicia sesión
      $_SESSION['user_id'] = $user_id;
      $_SESSION['name']    = strstr($email,'@',true);
      $_SESSION['role']    = 'reporter';

      // limpia
      unset($_SESSION['otp_email'], $_SESSION['otp_last_code']);

      header('Location: /alertard/index.php');
      exit;

echo json_encode(['ok'=>true]);


      
    
?>
<?php require __DIR__.'/../partials/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Verifica tu correo</h5>
        <p>Hemos enviado un código a <strong><?php echo htmlspecialchars($email); ?></strong>.
           Revisa tu bandeja de entrada (y spam).</p>

        <?php if ($cfg['debug_show_code'] && !empty($_SESSION['otp_last_code'])): ?>
          <div class="alert alert-warning">
            <strong>DEBUG:</strong> tu código es <code><?php echo htmlspecialchars($_SESSION['otp_last_code']); ?></code>
            (no mostrar en producción).
          </div>
        <?php endif; ?>

        <?php if ($msg): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <form action="/alertard/auth/email_verify.php" method="POST" id="form-verify">
          <div class="col-12">
            <input type="text" class="form-control" name="code" placeholder="Código de 6 dígitos" maxlength="6" required>
          </div>
          <div class="col-12 d-grid">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="email" name="email" class="form-control" required>
              <input type="text"  name="code"  class="form-control" maxlength="6" required>
            <button class="btn btn-success" type="submit">Entrar</button>
          </div>
        </form>

        <form method="post" action="/alertard/auth/email_start.php" class="mt-2">
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
          <button class="btn btn-link p-0">Reenviar código</button>
        </form>

        <div class="mt-3">
          <a href="/alertard/auth/login.php" class="small">Cambiar correo</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__.'/../partials/footer.php'; ?>
