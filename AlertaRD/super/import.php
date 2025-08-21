<?php require __DIR__.'/../partials/header.php'; ?>
<?php if (($role ?? 'guest')!=='validator' && ($role ?? 'guest')!=='admin'): ?>
<div class="alert alert-danger">Acceso restringido. Inicia sesión como validador.</div>
<?php require __DIR__.'/../partials/footer.php'; exit; endif; ?>

<h4 class="mb-3">Importación masiva (CSV)</h4>

<div class="row g-3">
  <!-- Provincias -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">Provincias</div>
      <div class="card-body">
        <form id="formProv">
          <input type="hidden" name="resource" value="provinces">
          <div class="mb-2">
            <input type="file" name="file" accept=".csv,text/csv" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Importar</button>
        </form>
        <hr>
        <div class="small text-muted">
          <strong>Columnas:</strong> <code>name</code><br>
          <strong>Ejemplo:</strong><br>
          <pre class="border p-2 rounded bg-light">name
Santo Domingo
Santiago
La Vega</pre>
        </div>
      </div>
    </div>
  </div>

  <!-- Municipios -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">Municipios</div>
      <div class="card-body">
        <form id="formMuni">
          <input type="hidden" name="resource" value="municipalities">
          <div class="mb-2">
            <input type="file" name="file" accept=".csv,text/csv" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Importar</button>
        </form>
        <hr>
        <div class="small text-muted">
          <strong>Columnas (usa 1 de estas dos opciones):</strong><br>
          Opción A: <code>name,province</code><br>
          Opción B: <code>name,province_id</code><br>
          <strong>Ejemplo A:</strong><br>
          <pre class="border p-2 rounded bg-light">name,province
Santo Domingo Este,Santo Domingo
Santo Domingo Norte,Santo Domingo
Santiago de los Caballeros,Santiago</pre>
        </div>
      </div>
    </div>
  </div>

  <!-- Barrios -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">Barrios</div>
      <div class="card-body">
        <form id="formBarr">
          <input type="hidden" name="resource" value="barrios">
          <div class="mb-2">
            <input type="file" name="file" accept=".csv,text/csv" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Importar</button>
        </form>
        <hr>
        <div class="small text-muted">
          <strong>Columnas (usa 1 de estas dos opciones):</strong><br>
          Opción A: <code>name,municipality,province</code> (recomendado)<br>
          Opción B: <code>name,municipality_id</code><br>
          <strong>Ejemplo A:</strong><br>
          <pre class="border p-2 rounded bg-light">name,municipality,province
El Almirante,Santo Domingo Este,Santo Domingo
Cienfuegos,Santiago de los Caballeros,Santiago</pre>
        </div>
      </div>
    </div>
  </div>
</div>

<hr>
<h6>Resultado</h6>
<pre id="result" class="border p-3 bg-light rounded small">Sin ejecutar</pre>

<script>
async function sendForm(formId) {
  const form = document.getElementById(formId);
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    const res = await fetch('/alertard/api/import_csv.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });
    const txt = await res.text();
    try {
      const json = JSON.parse(txt);
      document.getElementById('result').textContent = JSON.stringify(json, null, 2);
    } catch {
      document.getElementById('result').textContent = txt;
    }
  });
}
sendForm('formProv');
sendForm('formMuni');
sendForm('formBarr');
</script>

<?php require __DIR__.'/../partials/footer.php'; ?>
