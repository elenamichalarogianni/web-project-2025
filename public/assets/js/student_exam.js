(function(){
  const section   = document.getElementById('exam-report-section');
  if (!section) return;

  const btn       = document.getElementById('examReportBtn');
  const repoUrl   = document.getElementById('repoUrl');
  const repoTitle = document.getElementById('repoTitle');
  const repoSave  = document.getElementById('repoSave');
  const repoCur   = document.getElementById('repoCurrent');
  const msg       = document.getElementById('examMsg');

  async function loadMeta(){
    try{
      const tid = window.__studentThesisId;
      if (!tid){ section.hidden = true; return; }
      const r = await fetch(`api/student_exam_meta.php?thesis_id=${encodeURIComponent(tid)}`, {credentials:'same-origin'});
      const d = await r.json();
      if (!d.ok){ section.hidden = true; return; }

      section.hidden = !d.ready;
      if (d.ready) {
      
        if (window.__studentThesisStatus === 'Completed') {
          const repoTitleBox = document.getElementById('repoTitle')?.closest('label') || null;
          const repoRow = document.getElementById('repoUrl')?.closest('.file-row') || null;
          if (repoTitleBox) repoTitleBox.style.display = 'none';
          if (repoRow) repoRow.style.display = 'none';
        }
      }

      if (d.ready) {
        if (btn && d.report_url) btn.onclick = () => { window.location.href = d.report_url; };
        if (d.repository && d.repository.url) {
          repoCur.innerHTML = `<a href="${escapeHtml(d.repository.url)}" target="_blank" rel="noopener">${escapeHtml(d.repository.title||'Repository')}</a>`;
        } else {
          repoCur.textContent = '— καμία καταχώριση —';
        }
      }
    }catch{ section.hidden = true; }
  }

  if (repoSave){
    repoSave.addEventListener('click', async () => {
      msg.textContent = 'Αποθήκευση...'; msg.style.color='';
      const tid = window.__studentThesisId;
      const url = (repoUrl.value||'').trim();
      const ttl = (repoTitle.value||'').trim() || 'Repository';
      if (!/^https?:\/\//i.test(url)){ msg.style.color='#b00020'; msg.textContent='Δώσε έγκυρο URL (http/https).'; return; }
      const fd = new FormData();
      fd.append('thesis_id', tid);
      fd.append('url', url);
      fd.append('title', ttl);
      try{
        const r = await fetch('stud_set_repository.php',{method:'POST', body:fd, credentials:'same-origin'});
        const d = await r.json();
        if (!r.ok || !d.ok) throw new Error(d.error||`HTTP ${r.status}`);
        msg.style.color='#0a7a3e'; msg.textContent='Αποθηκεύτηκε.';
        repoCur.innerHTML = `<a href="${escapeHtml(d.url)}" target="_blank" rel="noopener">${escapeHtml(ttl)}</a>`;
        repoUrl.value=''; repoTitle.value='';
      }catch(e){
        msg.style.color='#b00020'; msg.textContent='Σφάλμα: '+e.message;
      }
    });
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m])); }

  
  document.addEventListener('DOMContentLoaded', () => {
   
    setTimeout(loadMeta, 50);
  });

  
  window.__reloadStudentExamMeta = loadMeta;
})();
