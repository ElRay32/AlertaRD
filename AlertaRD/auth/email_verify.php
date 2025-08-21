<?php
require __DIR__.'/email_lib.php';

$email = $_SESSION['otp_email'] ?? null;
if (!$email) { header('Location: /alertard/auth/login.php'); exit; }

$cfg = ecfg();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $code = trim($_POST['code'] ?? '');
  if (!preg_match('/^\d{6}$/', $code)) {
    $msg = 'Código inválido.';
  } else {
    $pdo = dbx();
    $sel = $pdo->prepare("SELECT id, expires_at, used FROM login_tokens
                          WHERE email=? AND code=? ORDER BY id DESC LIMIT 1");
    $sel->execute([$email, $code]);
    $t = $sel->fetch();

    if (!$t) {
      $msg = 'Código incorrecto.';
    } elseif ((int)$t['used']===1) {
      $msg = 'Código ya usado.';
    } elseif (strtotime($t['expires_at']) < time()) {
      $msg = 'Código expirado.';
    } else {
      // marca usado
      $upd = $pdo->prepare("UPDATE login_tokens SET used=1 WHERE id=?");
      $upd->execute([$t['id']]);

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
    }
  }
}
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

        <form method="post" class="row g-2">
          <div class="col-12">
            <input type="text" class="form-control" name="code" placeholder="Código de 6 dígitos" maxlength="6" required>
          </div>
          <div class="col-12 d-grid">
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
