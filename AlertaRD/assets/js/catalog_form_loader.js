// /AlertaRD/assets/js/catalog_form_loader.js
// Inicializa selects dependientes: Provincias -> Municipios -> Barrios, y multi-select de Tipos.
(function(){
  function detectBase(){
    // /AlertaRD/loquesea.php  OR  /AlertaRD/super/loquesea.php
    var m = location.pathname.match(/^(.*?)(?:\/super)?\/[^\/]+$/);
    return m ? m[1] : '';
  }
  function opt(value, text){ var o = document.createElement('option'); o.value = value; o.textContent = text; return o; }
  function clearSel(sel, placeholder){
    while (sel.firstChild) sel.removeChild(sel.firstChild);
    if (placeholder !== null) sel.appendChild(opt('', placeholder || '(Seleccione)'));
  }
  function enable(sel, on){ sel.disabled = !on; sel.classList.toggle('disabled', !on); }

  async function apiGetJSON(url){
    const r = await fetch(url, {credentials:'same-origin'});
    if(!r.ok) throw new Error('GET '+r.status+' '+url);
    return r.json();
  }

  async function loadProvinces(base, selProv){
    clearSel(selProv, '(Seleccione)');
    enable(selProv, false);
    const j = await apiGetJSON(base + '/api/catalog_list.php?entity=provinces');
    const rows = (j && j.ok && j.data) ? j.data : [];
    rows.forEach(r => selProv.appendChild(opt(r.id, r.name)));
    enable(selProv, true);
  }

  async function loadMunicipalities(base, selMuni, provinceId){
    clearSel(selMuni, '(Seleccione)');
    enable(selMuni, false);
    if (!provinceId){ enable(selMuni, true); return; }
    const j = await apiGetJSON(base + '/api/catalog_list.php?entity=municipalities&province_id=' + encodeURIComponent(provinceId));
    const rows = (j && j.ok && j.data) ? j.data : [];
    rows.forEach(r => selMuni.appendChild(opt(r.id, r.name)));
    enable(selMuni, true);
  }

  async function loadBarrios(base, selBarrio, municipalityId){
    clearSel(selBarrio, '(Seleccione)');
    enable(selBarrio, false);
    if (!municipalityId){ enable(selBarrio, true); return; }
    const j = await apiGetJSON(base + '/api/catalog_list.php?entity=barrios&municipality_id=' + encodeURIComponent(municipalityId));
    const rows = (j && j.ok && j.data) ? j.data : [];
    rows.forEach(r => selBarrio.appendChild(opt(r.id, r.name)));
    enable(selBarrio, true);
  }

  async function loadTypes(base, selTypes){
    // Para multi-select; no agregamos placeholder
    selTypes.innerHTML = '';
    const j = await apiGetJSON(base + '/api/catalog_list.php?entity=types');
    const rows = (j && j.ok && j.data) ? j.data : [];
    rows.forEach(r => selTypes.appendChild(opt(r.id, r.name)));
    enable(selTypes, true);
  }

  // API pública
  window.initCatalogDependentSelects = async function(opts){
    // opts: { base?, selProvince, selMunicipality, selBarrio, selTypes }
    const base = (opts && opts.base) || detectBase();
    const selProv = (typeof opts.selProvince === 'string') ? document.querySelector(opts.selProvince) : opts.selProvince;
    const selMuni = (typeof opts.selMunicipality === 'string') ? document.querySelector(opts.selMunicipality) : opts.selMunicipality;
    const selBarr = (typeof opts.selBarrio === 'string') ? document.querySelector(opts.selBarrio) : opts.selBarrio;
    const selTypes = (typeof opts.selTypes === 'string') ? document.querySelector(opts.selTypes) : opts.selTypes;

    if (!selProv || !selMuni || !selBarr || !selTypes) {
      console.warn('[catalog_form_loader] Faltan selectores/elementos en initCatalogDependentSelects');
      return;
    }

    // Asegura que no estén disabled por HTML
    [selProv, selMuni, selBarr, selTypes].forEach(function(s){ try{s.disabled=false;}catch(e){} });

    await loadProvinces(base, selProv);
    await loadTypes(base, selTypes);

    selProv.addEventListener('change', async function(){
      await loadMunicipalities(base, selMuni, selProv.value);
      await loadBarrios(base, selBarr, null);
    });
    selMuni.addEventListener('change', function(){ loadBarrios(base, selBarr, selMuni.value); });
  };
})();
