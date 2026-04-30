(function () {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const err  = document.getElementById('loginError');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (err) { err.style.display = 'none'; err.textContent = ''; }

    const fd = new FormData(form);

    try {
      const res = await fetch('api/auth.login.php', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();

      if (!data.ok) {
        if (err) {
          err.textContent = (data.error === 'EMPTY_FIELDS')
            ? 'Συμπλήρωσε email και κωδικό.'
            : 'Λάθος email ή κωδικός!';
          err.style.display = 'block';
        } else {
          alert('Λάθος email ή κωδικός!');
        }
        return;
      }

      
      if (data.role === 'Professor')      window.location.href = 'professor.php';
      else if (data.role === 'Student')   window.location.href = 'student.php';
      else if (data.role === 'Secretary') window.location.href = 'secretary.php';
      else                                window.location.href = 'professor.php';
    } catch (ex) {
      if (err) { err.textContent = 'Πρόβλημα σύνδεσης με τον server.'; err.style.display = 'block'; }
      else { alert('Πρόβλημα σύνδεσης με τον server.'); }
    }
  });
})();