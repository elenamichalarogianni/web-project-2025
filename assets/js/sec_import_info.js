
(function () {
  const form = document.getElementById('importForm');
  const out  = document.getElementById('output');

  if (!form || !out) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    out.hidden = false;
    out.textContent = 'Running import…';

  
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    try {
      const formData = new FormData(form);
      const res = await fetch(form.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',        
        headers: { 'Accept': 'application/json' }
      });

      const txt = await res.text();
      try {
        const obj = JSON.parse(txt);
        out.textContent = JSON.stringify(obj, null, 2);
      } catch {
        out.textContent = txt;
      }
    } catch (err) {
      out.textContent = 'Failed: ' + err;
    } finally {
      if (btn) btn.disabled = false;
    }
  });
})();