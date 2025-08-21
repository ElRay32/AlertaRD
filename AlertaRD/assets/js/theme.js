// AlertARD Theme JS
(function(){
  const KEY = 'alertard_theme_light';
  const btn = document.getElementById('btnTheme');
  const apply = (light) => {
    document.body.classList.toggle('light', !!light);
    if (btn) btn.title = light ? 'Cambiar a oscuro' : 'Cambiar a claro';
  };
  const saved = localStorage.getItem(KEY) === '1';
  apply(saved);
  if (btn) {
    btn.addEventListener('click', ()=>{
      const next = !document.body.classList.contains('light');
      localStorage.setItem(KEY, next ? '1':'0');
      apply(next);
    });
  }
  // Activa enlace activo en sidebar
  const links = document.querySelectorAll('.nav-vertical a[data-route]');
  const path = location.pathname;
  links.forEach(a=>{
    const r = a.getAttribute('data-route');
    if (r && path.endsWith(r)) a.classList.add('active');
  });
})();
