
document.addEventListener('DOMContentLoaded', () => {
  const ctx = (window.SEC_GA_CTX || {});
  if (ctx.role !== 'Secretary') return;

  const btn = document.getElementById('gaSaveBtn');
  const msg = document.getElementById('gaMsg');
  const num = document.getElementById('ga_number');
  const yr  = document.getElementById('ga_year');

  if (!btn || !num || !yr || !msg) return;

  function showMsg(text, ok = true) {
    msg.textContent = text || '';
    msg.style.display = text ? '' : 'none';
    msg.className = 'notice ' + (ok ? 'success' : 'error');
  }

  btn.addEventListener('click', async () => {
    const ga_number = parseInt(num.value || '0', 10);
    const ga_year   = parseInt(yr.value  || '0', 10);

    if (!(ga_number > 0) || !(ga_year >= 2000 && ga_year <= 2100)) {
      showMsg('Συμπλήρωσε έγκυρο ΑΠ/Έτος.', false);
      return;
    }

    btn.disabled = true;
    showMsg('Saving…', true);

    try {
      const fd = new FormData();
      fd.append('thesis_id', String(ctx.thesisId || 0));
      fd.append('ga_number', String(ga_number));
      fd.append('ga_year',   String(ga_year));

      const r  = await fetch('api/secretary_save_ga.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      });
      const d = await r.json();
      if (!r.ok || !d.ok) throw new Error(d.error || r.status);
      showMsg('GA info saved.');
    } catch (e) {
      console.error(e);
      showMsg('Αποτυχία αποθήκευσης.', false);
    } finally {
      btn.disabled = false;
    }
  });
});
