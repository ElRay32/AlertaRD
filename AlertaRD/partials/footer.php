    </main>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet (si lo usas en la página) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- GLightbox (si lo usas en la página) -->
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

<!-- Theme JS -->
<script src="/alertard/assets/js/theme.js"></script>

<script>
// Helpers fetch con CSRF
window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
async function apiGet(url){ const r = await fetch(url, {credentials:'same-origin'}); return r.json(); }
async function apiPost(url, data){
  let body, headers = {'X-CSRF-Token': window.CSRF_TOKEN};
  if (data instanceof FormData || data instanceof URLSearchParams) body = data;
  else { headers['Content-Type'] = 'application/json'; body = JSON.stringify(data); }
  const r = await fetch(url, {method:'POST', headers, body, credentials:'same-origin'});
  return r.json();
}
</script>

</body>
</html>
