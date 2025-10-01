(function () {
  const $ = id => document.getElementById(id);

  const titleEl   = $('st-title');
  const descEl    = $('st-desc');
  const fileEl    = $('st-file');
  const dlEl      = $('st-download');
  const statusEl  = $('st-status');
  const elapsedEl = $('st-elapsed');   

  const listEl    = $('committeeList');
  const sendBtn   = $('sendInvBtn');

  const draftBox = $('draft-upload');
  const draftInput = $('draftFile');
  const draftBtn = $('draftUploadBtn')
  const draftName = $('draftFileName');
  const draftCur  = $('draftCurrentLink');
  const draftNone = $('draftNone');

   
  const linksBox   = $('links-box');
  const linkTitle  = $('linkTitle');
  const linkUrl    = $('linkUrl');
  const linkAddBtn = $('linkAddBtn');
  const linkList   = $('linkList');

  function li(text){ return `<li>${text}</li>`; }
  
  function humanElapsed(iso){
    const start = new Date(iso);
    const now   = new Date();
    if (isNaN(start)) return '—';
    let diff = Math.max(0, now - start);
    const sec = Math.floor(diff/1000);
    const min = Math.floor(sec/60);
    const hrs = Math.floor(min/60);
    const days = Math.floor(hrs/24);
    const months = Math.floor(days/30);
    const years  = Math.floor(days/365);
    if (years >= 1) {
      const remM = Math.floor((days - years*365)/30);
      return years + ' ' + (years===1?'χρόνος':'χρόνια') +
             (remM>0 ? ' και ' + remM + ' ' + (remM===1?'μήνας':'μήνες') : '');
    }
    if (months >= 1) {
      const remD = days - months*30;
      return months + ' ' + (months===1?'μήνας':'μήνες') +
             (remD>0 ? ' και ' + remD + ' ' + (remD===1?'ημέρα':'ημέρες') : '');
    }
    if (days >= 1) return days + ' ' + (days===1?'ημέρα':'ημέρες');
    if (hrs  >= 1) return hrs  + ' ' + (hrs===1 ?'ώρα' :'ώρες');
    if (min  >= 1) return min  + ' ' + (min===1 ?'λεπτό':'λεπτά');
    return sec + ' ' + (sec===1?'δευτερόλεπτο':'δευτερόλεπτα');
  }

  async function load() {
    try {
      const res = await fetch('api/student_thesis.php', { credentials:'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.thesis) {
      
        titleEl.value  = '';
        descEl.value   = '';
        fileEl.value   = '';
        statusEl.textContent = '—';
        dlEl.hidden = true;

        if (listEl){
          listEl.innerHTML = li('— δεν υπάρχει ανατεθειμένη διπλωματική —');
        }
        if (sendBtn) { sendBtn.hidden = true; sendBtn.disabled = true; }
        return;
      }

      const t = data.thesis;
      window.__studentThesisId = t.ThesisID;
      window.__studentThesisStatus = t.ThesisStatus || '';

     
      titleEl.value  = t.Title || '';
      descEl.value   = t.ThesisDescription || '';
      fileEl.value   = t.Link || '';
      statusEl.textContent = t.ThesisStatus || '—';
      if (t.Link) { dlEl.href = t.Link; dlEl.hidden = false; } else { dlEl.hidden = true; }

      
      if (elapsedEl) {
        elapsedEl.hidden = true;
        const assignedAt = t.AssignedAt; 
        if (assignedAt && t.ThesisStatus !== 'Under Assignment') {
          elapsedEl.textContent = 'Χρόνος από ανάθεση: ' + humanElapsed(assignedAt);
          elapsedEl.hidden = false;
        }
      }

     
      const members = Array.isArray(t.Committee) ? t.Committee : [];
      if (listEl){
        if (members.length === 0){
          listEl.innerHTML = li('— δεν έχουν προστεθεί μέλη ακόμη —');
        } else {
          listEl.innerHTML = members.map(m =>
            `<li><strong>${escapeHtml(m.MemberType)}:</strong> ${escapeHtml(m.FullName || ('Καθηγητής #'+m.ProfessorID))}</li>`
          ).join('');
        }
      }

     
      
      if (sendBtn){
        const nonSupervisorCount = members.filter(m => (m.MemberType || '').toLowerCase() !== 'supervisor').length;
        const allowInvitations = (t.ThesisStatus === 'Under Assignment') && (nonSupervisorCount < 2);
        sendBtn.hidden   = !allowInvitations;
        sendBtn.disabled = !allowInvitations; 
      }

      
      
        
        
      
           
      const canUpload = (t.ThesisStatus === 'Under Review');

      if (draftBox){
        draftBox.hidden = !canUpload;
        
        if (draftCur && draftNone){
          if (t.Link){
            draftCur.href = t.Link;
            draftCur.hidden = false;
            draftNone.hidden = true;
          } else {
            draftCur.hidden = true;
            draftNone.hidden = false;
          }
        }
      }

      if (linksBox){
        linksBox.hidden = !canUpload;
        if (canUpload){
          
          await loadLinks(t.ThesisID);
        } else if (linkList){
          linkList.innerHTML = '';
        }
      }

    } catch {
      
    }
  }

  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
  }
  
    
  if (draftBtn && draftInput) {
    draftBtn.addEventListener('click', async () => {
      const tid = window.__studentThesisId;
      const f = draftInput.files && draftInput.files[0];
      if (!tid) { alert('Δεν βρέθηκε ThesisID.'); return; }
      if (!f)   { alert('Επίλεξε αρχείο (.pdf/.doc/.docx).'); return; }

      const fd = new FormData();
      fd.append('thesis_id', tid);
      fd.append('file', f);

      try {
        const res  = await fetch('api/upload_draft.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (!res.ok || !data.ok) {
          alert('Αποτυχία μεταφόρτωσης: ' + (data.error || `HTTP ${res.status}`));
          return;
        }

        
        fileEl.value = data.link || '';
        if (data.link) { dlEl.href = data.link; dlEl.hidden = false; }

        
        const draftCur  = document.getElementById('draftCurrentLink');
        const draftNone = document.getElementById('draftNone');
        const draftName = document.getElementById('draftFileName');
        if (draftCur && draftNone && data.link) {
          draftCur.href   = data.link;
          draftCur.hidden = false;
          draftNone.hidden = true;
        }
        if (draftName) draftName.value = '';
        draftInput.value = '';

        alert('Το αρχείο ανέβηκε επιτυχώς.');
      } catch {
        alert('Σφάλμα δικτύου.');
      }
    });
  }

  if (draftInput && draftName){
    draftInput.addEventListener('change', () => {
      draftName.value = (draftInput.files && draftInput.files[0] && draftInput.files[0].name) || '';
    });
  }

    async function loadLinks(thesisId){
    if (!linkList) return;
    linkList.innerHTML = '<li>Φόρτωση...</li>';
    try{
      const r = await fetch(`api/student_files.php?thesis_id=${encodeURIComponent(thesisId)}`, {credentials:'same-origin'});
      const d = await r.json();
      if(!d.ok){ linkList.innerHTML = '<li>Σφάλμα φόρτωσης.</li>'; return; }

      const links = (d.items || []).filter(x => x.FileType === 'Link');
      if (links.length === 0){
        linkList.innerHTML = '<li>— κανένας σύνδεσμος ακόμη —</li>';
        return;
      }

      linkList.innerHTML = links.map(it => `
        <li data-id="${it.FileID}" class="link-row"
          <div class="link-text">
            <a href="${escapeHtml(it.FilePath)}" target="_blank" rel="noopener">
              ${escapeHtml(it.Description || it.FilePath)}
            </a>
          </div>
          <button type="button" class="download-btn js-del-link">Διαγραφή</button>
        </li>
      `).join('');
    }catch{
      linkList.innerHTML = '<li>Σφάλμα δικτύου.</li>';
    }
  }
  
  if (linkAddBtn && linkUrl){
    linkAddBtn.addEventListener('click', async () => {
      const tid = window.__studentThesisId;
      const url = (linkUrl.value || '').trim();
      const ttl = (linkTitle && linkTitle.value || '').trim();
      if (!tid){ alert('Δεν βρέθηκε ThesisID.'); return; }
      if (!/^https?:\/\//i.test(url)){ alert('Συμπλήρωσε έγκυρο σύνδεσμο που αρχίζει από http(s)://'); return; }

      const fd = new FormData();
      fd.append('thesis_id', tid);
      fd.append('url', url);
      fd.append('title', ttl);

      try{
        const r = await fetch('stud_add_link.php', { method:'POST', body: fd, credentials:'same-origin' });
        const d = await r.json();
        if(!r.ok || !d.ok){ alert('Αποτυχία: ' + (d.error || `HTTP ${r.status}`)); return; }
        
        if (linkTitle) linkTitle.value = '';
        linkUrl.value = '';
        await loadLinks(tid);
      }catch{
        alert('Σφάλμα δικτύου.');
      }
    });
  }

  
  if (linkList){
    linkList.addEventListener('click', async (e) => {
      const btn = e.target.closest('.js-del-link');
      if (!btn) return;
      const li = btn.closest('li[data-id]');
      const id = li ? +li.dataset.id : 0;
      if (!id) return;
      if (!confirm('Διαγραφή συνδέσμου;')) return;

      const fd = new FormData();
      fd.append('file_id', id);

      try{
        const r = await fetch('stud_delete_file.php', { method:'POST', body: fd, credentials:'same-origin' });
        const d = await r.json();
        if(!r.ok || !d.ok){ alert('Αποτυχία: ' + (d.error || `HTTP ${r.status}`)); return; }
        li.remove();
      }catch{
        alert('Σφάλμα δικτύου.');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', load);
  window.__reloadStudentThesis = load;
})();

(function(){
  const openBtn = document.getElementById('sendInvBtn');
  const modal   = document.getElementById('inviteModal');
  if (!openBtn || !modal) return;

  const closeBtn = document.getElementById('inviteClose');
  const listBox  = document.getElementById('eligibleList');
  const sendBtn  = document.getElementById('inviteSend');

  function open(){ modal.hidden=false; document.body.style.overflow='hidden'; loadEligible(); }
  function close(){ modal.hidden=true; document.body.style.overflow=''; listBox.innerHTML=''; }

  openBtn.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  modal.addEventListener('click', (e)=>{ if (e.target===modal) close(); });

  async function loadEligible(){
    const tid = window.__studentThesisId;
    if (!tid){ listBox.innerHTML='<div class="hint">Δεν βρέθηκε ThesisID.</div>'; return; }

    try{
      const res = await fetch(`api/eligible_professors.php?thesis_id=${encodeURIComponent(tid)}`, {credentials:'same-origin'});
      const data = await res.json();
      if(!data.ok){ listBox.innerHTML='<div class="hint">Σφάλμα φόρτωσης.</div>'; return; }

      if(!data.items || data.items.length===0){
        listBox.innerHTML = '<div class="hint">Δεν υπάρχουν διαθέσιμοι καθηγητές για πρόσκληση.</div>';
        return;
      }

      listBox.innerHTML = `
        <form id="eligibleForm" class="eligible-form">
          ${data.items.map(p => `
            <label class="pick-row">
              <input type="checkbox" name="prof" value="${p.ProfessorID}">
              <span class="name">${escapeHtml(p.FullName)}</span>
              <span class="email">${escapeHtml(p.Email||'')}</span>
            </label>
          `).join('')}
        </form>
      `;
    }catch{
      listBox.innerHTML='<div class="hint">Σφάλμα δικτύου.</div>';
    }
  }

  sendBtn.addEventListener('click', async () => {
    const tid  = window.__studentThesisId;
    const form = document.getElementById('eligibleForm');
    if (!tid || !form) { alert('Κάτι πήγε στραβά.'); return; }

    const ids = Array.from(form.querySelectorAll('input[name="prof"]:checked')).map(i => +i.value);
    if (ids.length === 0) { alert('Επίλεξε τουλάχιστον έναν καθηγητή.'); return; }

    try {
      const res  = await fetch('api/send_invitations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ thesis_id: tid, professor_ids: ids }),
      });

      const raw  = await res.text();
      let data   = null;
      try { data = JSON.parse(raw); } catch {  }

      if (!res.ok || !data || data.ok === false) {
        const msg = (data && (data.error || data.message)) || raw || `HTTP ${res.status}`;
        alert('Αποτυχία: ' + msg);
        return;
      }

      alert(`Εστάλησαν ${data.created} προσκλήσεις.`);
      modal.hidden = true;
      document.body.style.overflow = '';
      if (window.__reloadStudentThesis) window.__reloadStudentThesis(); 

    } catch (e) {
      console.error(e);
      alert('Σφάλμα δικτύου.');
    }
  });

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m])); }
})();
