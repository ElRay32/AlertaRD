// /AlertaRD/assets/js/dependent_selects_localfilter.js
// Carga TODAS las listas y filtra en el cliente; evita depender de filtros del backend.
(function(){
  function detectBase(){
    var m = location.pathname.match(/^(.*?)(?:\/super)?\/[^\/]+$/);
    return m ? m[1] : '';
  }
  function opt(v,t){ var o=document.createElement('option'); o.value=v; o.textContent=t; return o; }
  function fill(sel, rows, placeholder){
    sel.innerHTML = '';
    if (placeholder !== null) sel.appendChild(opt('', placeholder || '(Seleccione)'));
    rows.forEach(r => sel.appendChild(opt(r.id, r.name)));
  }
  function enable(sel,on){ sel.disabled = !on; }

  async function getJSON(url){
    const r = await fetch(url, {credentials:'same-origin'});
    if(!r.ok) throw new Error('GET '+r.status+' '+url);
    return r.json();
  }

  window.initLocalDependentSelects = async function(cfg){
    const base = (cfg && cfg.base) || detectBase();
    const selProv = typeof cfg.selProvince==='string' ? document.querySelector(cfg.selProvince) : cfg.selProvince;
    const selMuni = typeof cfg.selMunicipality==='string' ? document.querySelector(cfg.selMunicipality) : cfg.selMunicipality;
    const selBarr = typeof cfg.selBarrio==='string' ? document.querySelector(cfg.selBarrio) : cfg.selBarrio;
    if (!selProv || !selMuni || !selBarr){ console.warn('Faltan selects'); return; }

    // 1) Cargar todas las listas en paralelo
    const [p, m, b] = await Promise.all([
      getJSON(base + '/api/provinces_list.php'),
      getJSON(base + '/api/municipalities_list.php'),
      getJSON(base + '/api/barrios_list.php'),
    ]);
    const provinces = p.data || [];
    const municipalities = m.data || [];
    const barrios = b.data || [];

    // 2) Provincias
    fill(selProv, provinces, '(Seleccione)'); enable(selProv, true);

    // 3) Encadenado local
    selProv.addEventListener('change', function(){
      const pid = selProv.value ? parseInt(selProv.value,10) : null;
      const ms = pid ? municipalities.filter(x => Number(x.province_id) === pid) : [];
      fill(selMuni, ms, '(Seleccione)'); enable(selMuni, true);
      fill(selBarr, [], '(Seleccione)'); enable(selBarr, false);
    });
    selMuni.addEventListener('change', function(){
      const mid = selMuni.value ? parseInt(selMuni.value,10) : null;
      const bs = mid ? barrios.filter(x => Number(x.municipality_id) === mid) : [];
      fill(selBarr, bs, '(Seleccione)'); enable(selBarr, true);
    });
  };
})();