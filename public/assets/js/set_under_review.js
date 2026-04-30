(function(){
  const form = document.getElementById('setUnderReviewForm');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    try{
      const r = await fetch('update_thesis_status.php', {
        method:'POST',
        body: fd,
        credentials:'same-origin',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
      });
      const d = await r.json();
      if(!r.ok || !d.ok) throw new Error(d.error || ('HTTP '+r.status));
      location.reload();
    }catch(err){
      alert('Αποτυχία αλλαγής κατάστασης: '+err.message);
    }
  });
})();
