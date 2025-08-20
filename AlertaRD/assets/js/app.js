async function apiGet(url) {
  const res = await fetch(url, {credentials:'same-origin'});
  if (!res.ok) throw new Error('GET ' + url + ' -> ' + res.status);
  return res.json();
}
async function apiPost(url, data) {
  const res = await fetch(url, {
    method:'POST',
    body: data instanceof FormData ? data : JSON.stringify(data),
    headers: data instanceof FormData ? {} : {'Content-Type':'application/json' },
    credentials:'same-origin'
  });
  if (!res.ok) {
    let text = await res.text();
    throw new Error('POST ' + url + ' -> ' + res.status + ' ' + text);
  }
  return res.json();
}
function formatDateTime(s) {
  if (!s) return '';
  const d = new Date(s.replace(' ', 'T'));
  return d.toLocaleString();
}
