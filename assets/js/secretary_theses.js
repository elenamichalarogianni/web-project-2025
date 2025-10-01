document.addEventListener('DOMContentLoaded', async () => {
  const box = document.getElementById('sec-theses');
  if (!box) return;

  box.innerHTML = '<div class="empty-state">Φόρτωση…</div>';

  const groupsOrder = [
    ['Under Assignment','is-under-assignment'],
    ['Active','is-active'],
    ['Under Review','is-under-review']
  ];

  try {
    const r = await fetch('api/secretary_theses.php', { credentials: 'same-origin' });
    const d = await r.json();
    if (!r.ok || !d.ok) throw new Error(d.error || r.status);

    const items = Array.isArray(d.items) ? d.items : [];
    if (items.length === 0) {
      box.innerHTML = '<div class="empty-state">No theses with status “Under Assignment”, “Active”, or “Under Review”.</div>';
      return;
    }

    
    const byStatus = { 'Under Assignment':[], 'Active':[], 'Under Review':[] };
    for (const t of items) {
      if (byStatus[t.ThesisStatus]) byStatus[t.ThesisStatus].push(t);
    }

    
    box.innerHTML = groupsOrder.map(([status, css]) => {
      const rows = byStatus[status] || [];
      if (rows.length === 0) return '';
      const table = `
        <div class="status-section">
          <h2>${escapeHtml(status)} <span class="count-pill">${rows.length}</span></h2>
          <table class="results-table">
            <thead>
              <tr>
                <th>ThesisID</th>
                <th>Title</th>
                <th>Status</th>
                <th>AssignmentDate</th>
                <th>StudentID</th>
                <th>SupervisorID</th>
                <th>TopicID</th>
                <th>Link</th>
              </tr>
            </thead>
            <tbody>
              ${rows.map(r => `
                <tr>
                  <td>${+r.ThesisID}</td>
                  <td data-label="Title">
                    <a class="title-link" href="sec_thesis_details.php?thesisid=${+r.ThesisID}">
                      ${escapeHtml(r.Title || '')}
                    </a>
                  </td>
                  <td><span class="status-pill ${css}">${escapeHtml(r.ThesisStatus||'')}</span></td>
                  <td>${escapeHtml(r.AssignmentDate || '')}</td>
                  <td>${r.StudentID ? +r.StudentID : '—'}</td>
                  <td>${r.SupervisorID ? +r.SupervisorID : '—'}</td>
                  <td>${r.TopicID ? +r.TopicID : '—'}</td>
                  <td>${r.Link ? `<a href="${escapeAttr(r.Link)}" target="_blank" rel="noopener">Open</a>` : '—'}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>`;
      return table;
    }).join('') || '<div class="empty-state">No data.</div>';

  } catch (e) {
    console.error(e);
    box.innerHTML = '<div class="empty-state">Σφάλμα φόρτωσης.</div>';
  }
});

function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m])); }
function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }

