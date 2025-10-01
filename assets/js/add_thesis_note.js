(function(){
  const form = document.getElementById('noteForm');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    try{
      const r = await fetch(form.action || 'add_thesis_note.php', {
        method:'POST',
        body: fd,
        credentials:'same-origin',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
      });
      const d = await r.json();
      if(!r.ok || !d.ok) throw new Error(d.error || ('HTTP '+r.status));
      
      form.noteText.value = '';
      location.reload();
    }catch(err){
      alert('Αποτυχία αποθήκευσης σημείωσης: '+err.message);
    }
  });
})();
