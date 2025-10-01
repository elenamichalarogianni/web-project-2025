
document.addEventListener("DOMContentLoaded", () => {
  const form      = document.getElementById('searchForm');
  const searchBtn = document.getElementById('searchBtn');
  const qInput    = document.getElementById('search');
  const box       = document.getElementById('resultsBox');
  const assignBtn = document.getElementById('assignBtn');
  const assignMsg = document.getElementById('assignMessage');

  
  document.addEventListener('submit', (e) => {
    if (e.target && e.target.id === 'searchForm') e.preventDefault();
  }, true);

  let selectedStudentId = null;

  
  if (searchBtn) searchBtn.addEventListener('click', () => doSearch());
  if (qInput) qInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
  });

  async function doSearch() {
    const q = (qInput?.value || '').trim();
    selectedStudentId = null;
    if (assignBtn) { assignBtn.disabled = true; assignBtn.textContent = 'Submit'; }
    renderMessage('');

    if (!q) { box.innerHTML = '<div class="empty-state">Type something…</div>'; return; }

    box.innerHTML = '<div>Loading…</div>';
    try {
      const res  = await fetch('api/student_search.php?q=' + encodeURIComponent(q));
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'API error');

      if (!data.items.length) {
        box.innerHTML = '<div class="empty-state"><div>No students found.</div><div class="hint">Try part of a name, email, or AM.</div></div>';
        return;
      }

      box.innerHTML = `
        <table class="results-table">
          <thead><tr><th>Full name</th><th>AM</th><th>Email</th></tr></thead>
          <tbody>
            ${data.items.map(s => `
              <tr class="clickable" data-sid="${s.StudentID}">
                <td>${escapeHtml(s.FullName || '')}</td>
                <td>${escapeHtml(String(s.AM || ''))}</td>
                <td>${s.Email ? `<a href="mailto:${escapeAttr(s.Email)}" class="mailto">${escapeHtml(s.Email)}</a>` : ''}</td>
              </tr>`).join('')}
          </tbody>
        </table>
      `;

      
      let active = null;
      box.addEventListener('click', (ev) => {
        const mail = ev.target.closest('a.mailto');
        if (mail && !(ev.ctrlKey || ev.metaKey)) ev.preventDefault();

        const tr = ev.target.closest('tr.clickable');
        if (!tr || !box.contains(tr)) return;

        if (active) active.classList.remove('active');
        tr.classList.add('active');
        active = tr;

        selectedStudentId = tr.dataset.sid || null;

        const fullName = (tr.cells?.[0]?.textContent || '').trim();
        if (assignBtn) {
          assignBtn.disabled = !selectedStudentId;
          assignBtn.textContent = selectedStudentId ? `Assign to ${fullName}` : 'Submit';
        }
      });

    } catch (err) {
      console.error(err);
      box.innerHTML = `<div style="color:#b00">Σφάλμα φόρτωσης.</div>`;
    }
  }

  
  if (assignBtn) {
    assignBtn.addEventListener('click', async () => {
      if (!selectedStudentId) return;
      assignBtn.disabled = true;
      renderMessage('Submitting…');

      try {
        const fd = new FormData();
        fd.append('topic_id', String(window.TOPIC_ID || 0));
        fd.append('student_id', String(selectedStudentId));

        const res  = await fetch('api/thesis_assign.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.ok) {
          const reason = data.error || 'UNKNOWN';
          let text = 'Assignment failed.';
          if (reason === 'TITLE_EXISTS')
            text = `A thesis with the same title already exists (ID: ${data.thesis_id || '-' }).`;
          else if (reason === 'STUDENT_EXISTS')
            text = `This student already has a thesis (ID: ${data.thesis_id || '-' }).`;
          else if (reason === 'TOPIC_ONGOING')
            text = `This topic already has an ongoing thesis (ID: ${data.thesis_id || '-' }).`;
          else if (reason === 'TOPIC_NOT_FOUND_OR_NOT_OWNER')
            text = 'Topic not found or not owned by you.';
          renderMessage(text, true);
          assignBtn.disabled = false;
          return;
        }

        
        renderMessage(`Assignment saved (ThesisID: ${data.thesis_id}).`);
        selectedStudentId = null;
        assignBtn.disabled = true;
        assignBtn.textContent = 'Submit';
        const act = box.querySelector('tr.active');
        if (act) act.classList.remove('active');

      } catch (e) {
        console.error(e);
        renderMessage('Πρόβλημα επικοινωνίας με τον server.', true);
        assignBtn.disabled = false;
      }
    });
  }

  function renderMessage(txt, isError=false){
    if (!assignMsg) return;
    assignMsg.textContent = txt || '';
    assignMsg.style.display = txt ? '' : 'none';
    assignMsg.className = 'notice ' + (isError ? 'error' : 'success');
  }

 
  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }
});
