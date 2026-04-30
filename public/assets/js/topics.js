(function () {
  const fileInput = document.getElementById('pdfFile');
  const pathInput = document.getElementById('pdfpath');
  const fileNameSpan = document.getElementById('pdfFileName');


  if (fileInput && pathInput) {
    fileInput.addEventListener('change', function () {
      const f = this.files && this.files[0];
      
      pathInput.value = this.value || (f ? f.name : '');
      
      if (fileNameSpan) fileNameSpan.textContent = f ? f.name : 'No file chosen';
    });
  }

  const listEl = document.getElementById('topicsList');
  const form   = document.getElementById('topicForm');
  const editOverlay = document.getElementById('editOverlay');
  const editForm    = document.getElementById('editForm');
  const editFileName   = document.getElementById('editPdfFileName'); 
  const editId      = document.getElementById('editId');
  const editTitle   = document.getElementById('editTitle');
  const editDesc    = document.getElementById('editDesc');
  const editPdf     = document.getElementById('editPdf');
  const editPdfFile = document.getElementById('editPdfFile');
  const editSaveBtn = document.getElementById('editSave');
  const editCancel  = document.getElementById('editCancel');

function openEditModal({id, title, summary, pdfpath}) {
  if (!editOverlay) return;
  editId.value    = id || '';
  editTitle.value = title || '';
  editDesc.value  = summary || '';
  editPdf.value   = pdfpath || '';
  editOverlay.classList.add('is-open');   
  editOverlay.style.display = '';         
  document.body.style.overflow = 'hidden';
}
function closeEditModal() {
  if (!editOverlay) return;
  editOverlay.classList.remove('is-open');
  editOverlay.style.display = 'none';
  document.body.style.overflow = '';
}



if (editPdfFile && editPdf) {
  editPdfFile.addEventListener('change', () => {
    const f = editPdfFile.files && editPdfFile.files[0];
    editPdf.value = editPdfFile.value || (f ? f.name : '');
    if (editFileName) editFileName.textContent = f ? f.name : 'No file chosen';
  });
}



if (editCancel) editCancel.addEventListener('click', closeEditModal);


if (editOverlay) {
  editOverlay.addEventListener('click', (e) => {
    if (e.target === editOverlay) closeEditModal();
  });
}


document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeEditModal();
});



if (editSaveBtn && editForm) {
  editSaveBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    const fd = new FormData(editForm);
    fd.append('mode', 'update'); 
    try {
      const res = await fetch('api/topics.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        closeEditModal();
        
        loadTopics();
      } else {
        alert('Αποτυχία ενημέρωσης θέματος.');
      }
    } catch {
      alert('Πρόβλημα επικοινωνίας με τον server.');
    }
  });
}


if (listEl) {
  listEl.addEventListener('click', (e) => {
    const btn = e.target.closest('.edit-btn');
    if (!btn) return;
    openEditModal({
      id: btn.dataset.id,
      title: btn.dataset.title,
      summary: btn.dataset.summary,
      pdfpath: btn.dataset.pdf
    });
  });
}


  async function loadTopics() {
    if (!listEl) return;
    listEl.innerHTML = '<li>Loading…</li>';
    try {
      const res = await fetch('api/topics.php');
      const data = await res.json();
      if (!data.ok) throw new Error('API error');

      if (!data.items.length) { listEl.innerHTML = '<li>— καμία εγγραφή —</li>'; return; }

      listEl.innerHTML = data.items.map(item => `
        <li style="margin-bottom:15px;border-bottom:1px solid #ddd;padding-bottom:10px">
          <strong>${escapeHtml(item.Title)}</strong><br>
          ${escapeHtml(item.Summary || '')}<br>
          ${item.PDFpath ? `<a href="${escapeAttr(item.PDFpath)}" target="_blank">View PDF</a>` : ''}
          <button class="edit-btn"
          data-id="${item.TopicID}"
          data-title="${escapeAttr(item.Title)}"
          data-summary="${escapeAttr(item.Summary || '')}"
          data-pdf="${escapeAttr(item.PDFpath || '')}"
          style="margin-left:10px">Επεξεργασία</button>    
        </li>
      `).join('');
    } catch {
      listEl.innerHTML = '<li style="color:#b00">Σφάλμα φόρτωσης.</li>';
    }
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const res = await fetch('api/topics.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok) {
          alert(data.error === 'TITLE_REQUIRED' ? 'Ο τίτλος είναι υποχρεωτικός.' : 'Αποτυχία.');
          return;
        }
        form.reset();
        loadTopics();
      } catch {
        alert('Πρόβλημα επικοινωνίας με τον server.');
      }
    });
  }

  
  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }

  
  loadTopics();
})();