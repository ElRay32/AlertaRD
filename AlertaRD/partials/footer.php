    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

window.BASE_URL = <?= json_encode($BASE_URL) ?>;

window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// arma URL absoluta sin duplicar BASE
  window.absUrl = function (url) {
    if (/^https?:\/\//i.test(url)) return url;                    // ya es absoluta
    if (window.BASE_URL && url.startsWith(window.BASE_URL)) return url; // ya viene con BASE
    if (url.startsWith('/')) return (window.BASE_URL || '') + url;       // /api/...
    return (window.BASE_URL || '') + '/' + url;                           // api/...
  };

  // === BEGIN ADD: util de fecha global ===
if (!window.formatDateTime) {
  window.formatDateTime = function (s) {
    if (!s) return '';
    s = String(s);
    // normaliza "YYYY-MM-DDTHH:MM:SS" a "YYYY-MM-DD HH:MM"
    s = s.replace('T', ' ').replace('.000000', '');
    return s.length >= 16 ? s.slice(0, 16) : s;
  };
}
// === END ADD ===


  window.apiGet = async function (url) {
    const abs = window.absUrl(url);
    const r = await fetch(abs, { credentials: 'same-origin' });
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  };

  window.apiPost = async function (url, data) {
    const abs = window.absUrl(url);
    const token = window.CSRF_TOKEN;
    const headers = { 'Accept': 'application/json', 'X-CSRF-Token': token };
    let body;

    if (data instanceof FormData) {
      body = data; // no pongas Content-Type
    } else {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify({ ...(data || {}), csrf_token: token });
    }

    const r = await fetch(abs, { method: 'POST', headers, body, credentials: 'same-origin' });
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  };

document.addEventListener('DOMContentLoaded', function () {
  // Sube todos los modales al <body> para evitar stacking context
  document.querySelectorAll('.modal').forEach(function (m) {
    if (m.parentElement !== document.body) document.body.appendChild(m);
  });
});

</script>
</body>
</html>
