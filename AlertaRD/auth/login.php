<?php
$title = 'Entrar';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-3">Entrar</h4>

          <!-- PASO 1: pedir email -->
          <form id="emailForm" class="vstack gap-3">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div>
              <label class="form-label">Tu correo</label>
              <input type="email" name="email" class="form-control" placeholder="tucorreo@gmail.com" required>
              <div class="form-text">Te enviaremos un código de 6 dígitos.</div>
            </div>
            <div class="d-flex gap-2">
              <button id="btnSendCode" class="btn btn-primary">Enviar código</button>
              <a class="btn btn-outline-secondary" href="<?= $BASE_URL ?>/index.php">Cancelar</a>
            </div>
          </form>

          <!-- PASO 2: verificar código (oculto hasta que se envía) -->
          <form id="codeForm" class="vstack gap-3 mt-4 d-none">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="email" id="codeEmailHidden">
            <div>
              <label class="form-label">Código</label>
              <input type="text" name="code" class="form-control" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required>
              <div class="form-text">Revisa tu correo e ingresa el código. Expira en 10 minutos.</div>
            </div>
            <div class="d-flex gap-2">
              <button id="btnVerify" class="btn btn-success">Verificar</button>
              <button id="btnBack" type="button" class="btn btn-outline-secondary">Volver</button>
            </div>
          </form>

          <div id="msg" class="mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const BASE_URL = '<?= $BASE_URL ?>';
  const emailForm = document.getElementById('emailForm');
  const codeForm  = document.getElementById('codeForm');
  const msg       = document.getElementById('msg');
  const codeEmailHidden = document.getElementById('codeEmailHidden');
  const btnSendCode = document.getElementById('btnSendCode');
  const btnVerify   = document.getElementById('btnVerify');
  const btnBack     = document.getElementById('btnBack');

  function uiMsg(html, type='info'){ msg.className = 'mt-3 alert alert-'+type; msg.innerHTML = html; }
  function showStep(step){
    if(step===1){ emailForm.classList.remove('d-none'); codeForm.classList.add('d-none'); }
    else { emailForm.classList.add('d-none'); codeForm.classList.remove('d-none'); }
  }

  emailForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    uiMsg('');
    btnSendCode.disabled = true;

    const fd = new FormData(emailForm);
    try{
      const r = await fetch(`${BASE_URL}/auth/email_start.php`, {method:'POST', body:fd});
      const j = await r.json();
      if(j.ok){
        const email = fd.get('email');
        codeEmailHidden.value = email;
        showStep(2);
        //let extra = j.dev_code ? ` <span class="badge bg-secondary">dev_code: ${j.dev_code}</span>` : '';
        uiMsg(`Código enviado a <strong>${email}</strong>.${extra}`, 'success');
      }else{
        uiMsg(j.error || 'No se pudo enviar el código', 'danger');
      }
    }catch(err){
      //uiMsg('Error de conexión', 'danger');
    }finally{
      btnSendCode.disabled = false;
    }
  });

  codeForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    uiMsg('');
    btnVerify.disabled = true;

    const fd = new FormData(codeForm);
    try{
      const r = await fetch(`${BASE_URL}/auth/email_verify.php`, {method:'POST', body:fd});
      const j = await r.json();
      if(j.ok){
        // redirige por rol
        //if(j.role === 'admin')      location.href = `${BASE_URL}/super/dashboard.php`;
        if(j.role === 'admin')      location.href = `${BASE_URL}/index.php`;
        else if(j.role === 'validator') location.href = `${BASE_URL}/super/reports.php`;
        else                         location.href = `${BASE_URL}/index.php`;
      }else{
        uiMsg(j.error || 'Código incorrecto', 'danger');
      }
    }catch(err){
      uiMsg('Error de conexión', 'danger');
    }finally{
      btnVerify.disabled = false;
    }
  });

  btnBack.addEventListener('click', ()=>{ showStep(1); uiMsg(''); });

  // Por si alguien entra directo a /auth/email_start.php, recuérdales usar /auth/login.php
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
