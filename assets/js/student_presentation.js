(function(){
  const box = document.getElementById('st-presentation');
  if (!box) return;

  const dateEl = document.getElementById('sp-exam-date');
  const timeEl = document.getElementById('sp-exam-time');
  const typeEl = document.getElementById('sp-type');
  const locEl  = document.getElementById('sp-loc');
  const noteEl = document.getElementById('sp-note');
  const saveBtn= document.getElementById('sp-save');
  const msgEl  = document.getElementById('sp-msg');
  const locLbl = document.getElementById('sp-loc-label');

  function setLocLabel(){
    if (!locLbl) return;
    if (typeEl.value === 'Online') {
      locLbl.textContent = 'Σύνδεσμος (URL)';
      if (!locEl.placeholder.includes('http')) locEl.placeholder = 'https://zoom.us/..., https://meet.google.com/...';
    } else {
      locLbl.textContent = 'Αίθουσα';
      if (locEl.placeholder.includes('http')) locEl.placeholder = 'π.χ. Αμφιθέατρο Β12';
    }
  }
  if (typeEl) typeEl.addEventListener('change', setLocLabel);

  async function loadThesis(){
    try{
      const r = await fetch('api/student_thesis.php', {credentials:'same-origin'});
      const d = await r.json();
      if (!d.ok || !d.thesis) { box.hidden = true; return; }
      const t = d.thesis;
      
      if (t.ThesisStatus !== 'Under Review') { box.hidden = true; return; }
      box.hidden = false;
      const tid = t.ThesisID;
      box.dataset.tid = tid;

     
      const rr = await fetch(`api/student_presentation.php?thesis_id=${encodeURIComponent(tid)}`, {credentials:'same-origin'});
      const dd = await rr.json();
      if (dd.ok && dd.presentation){
        dateEl.value = dd.presentation.ExamDate || '';
        timeEl.value = dd.presentation.ExamTime ? (dd.presentation.ExamTime.substring(0,5)) : '';
        typeEl.value = dd.presentation.PresentationType || 'InPerson';
        locEl.value  = dd.presentation.LocationOrLink || '';
        noteEl.value = dd.presentation.AnnouncementText || '';
        setLocLabel();
      } else {
        typeEl.value = 'InPerson';
        setLocLabel();
      }
    }catch(e){
      box.hidden = true;
    }
  }

  async function save(){
    msgEl.textContent = 'Αποθήκευση...';
    msgEl.style.color = '';
    const tid  = +box.dataset.tid || 0;
    const body = {
      thesis_id: tid,
      exam_date: (dateEl.value||'').trim(),
      exam_time: (timeEl.value||'').trim(),
      presentation_type: typeEl.value,
      location_or_link: (locEl.value||'').trim(),
      announcement_text: (noteEl.value||'').trim(),
    };
    try{
      const r = await fetch('api/student_presentation.php', {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(body)
      });
      const d = await r.json();
      if (!r.ok || !d.ok) throw new Error(d.error || `HTTP ${r.status}`);
      msgEl.style.color = '#0a7a3e';
      msgEl.textContent = 'Αποθηκεύτηκε.';
    }catch(e){
      msgEl.style.color = '#b00020';
      msgEl.textContent = 'Σφάλμα: ' + e.message;
    }
  }

  if (saveBtn) saveBtn.addEventListener('click', save);
  document.addEventListener('DOMContentLoaded', loadThesis);
})();
