(function () {
  const list = document.getElementById('invList');
  const msg  = document.getElementById('invMsg');

  async function load() {
    if (!list) return;
    list.innerHTML = '<li>Loading…</li>';
    try {
      const res = await fetch('api/invites.php');
      const data = await res.json();
      if (!data.ok) throw 0;
      if (!data.items.length) {
        list.innerHTML = '<li>— καμία εκκρεμής πρόσκληση —</li>';
        return;
      }
      list.innerHTML = data.items.map(it => `
        <li id="inv-${it.InvitationID}">
          <strong>Student: ${esc(it.StudentName)}</strong><br>
          Thesis Description: ${esc(it.ThesisDescription || '')}<br>
          <button data-id="${it.InvitationID}" data-act="Accepted">Accept</button>
          <button data-id="${it.InvitationID}" data-act="Rejected">Reject</button>
        </li>
      `).join('');
    } catch {
      list.innerHTML = '<li style="color:#b00">Σφάλμα φόρτωσης.</li>';
    }
  }

  if (list) {
    list.addEventListener('click', async (e) => {
      const b = e.target.closest('button[data-id][data-act]');
      if (!b) return;
      const id  = b.getAttribute('data-id');
      const act = b.getAttribute('data-act');
      const fd = new FormData();
      fd.append('invitation_id', id);
      fd.append('action', act);
      try {
        const res = await fetch('api/invites.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
          const li = document.getElementById('inv-'+id);
          if (li) li.remove();
          if (!list.children.length) list.innerHTML = '<li>— καμία εκκρεμής πρόσκληση —</li>';
        } else {
          alert('Αποτυχία ενημέρωσης.');
        }
      } catch {
        alert('Πρόβλημα επικοινωνίας με τον server.');
      }
    });
  }

  function esc(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m])); }

  load();
})();