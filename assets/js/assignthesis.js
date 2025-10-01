(async function () {
  const ul = document.getElementById('assignTopics');
  const empty = document.getElementById('assignEmpty');
  if (!ul) return;

  ul.innerHTML = '<li>Loading…</li>';
  try {
    const res = await fetch('api/topics_available.php');
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'API error');

    if (!data.items.length) {
      ul.style.display = 'none';
      if (empty) empty.style.display = '';
      return;
    }

    ul.style.display = '';
    if (empty) empty.style.display = 'none';

    ul.innerHTML = data.items.map(r => {
      const url = 'searchstudent.php?topic_id=' + encodeURIComponent(String(r.TopicID));
      return `
        <li>
          <a class="topic-title" href="${url}">${escapeHtml(r.Title || '')}</a>
          ${r.Summary ? `<div class="topic-summary">${escapeHtml(r.Summary)}</div>` : ''}
          ${r.PDFpath ? `<a class="pdf-link" href="${escapeAttr(r.PDFpath)}" target="_blank" rel="noopener">View PDF</a>` : ''}
        </li>`;
    }).join('');
  } catch (e) {
    ul.innerHTML = '<li style="color:#b00">Σφάλμα φόρτωσης.</li>';
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }
})();
