(function(){
  const $ = id => document.getElementById(id);
  const addr = $('pr-address');
  const email = $('pr-email');
  const mobile = $('pr-mobile');
  const land = $('pr-landline');
  const saveBtn = $('pr-save');

  async function loadProfile(){
    try{
      const res = await fetch('api/student_profile.php', {credentials:'same-origin'});
      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data.error||('HTTP '+res.status));
      const p = data.profile || {};
      addr.value   = p.Address || '';
      email.value  = p.Email || '';
      mobile.value = p.MobilePhone || '';
      land.value   = p.LandlinePhone || '';
    }catch(e){
      alert('Αποτυχία φόρτωσης προφίλ: '+e.message);
    }
  }

  async function saveProfile(){
    const payload = {
      Address: addr.value.trim(),
      Email: email.value.trim(),
      MobilePhone: mobile.value.trim(),
      LandlinePhone: land.value.trim()
    };
    try{
      const res = await fetch('api/student_profile.php', {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data.error||('HTTP '+res.status));
      alert('Αποθηκεύτηκε επιτυχώς.');
    }catch(e){
      alert('Αποτυχία αποθήκευσης: '+e.message);
    }
  }

  document.addEventListener('DOMContentLoaded', loadProfile);
  if (saveBtn) saveBtn.addEventListener('click', saveProfile);
})();
