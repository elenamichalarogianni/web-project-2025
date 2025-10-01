(function () {
  const init = () => {
    const form = document.getElementById('gradeForm');
    if (!form) return;

    const finalOutput = document.getElementById('finalScore');
    const statusBox   = document.getElementById('gradeStatus');

    function updateFinal() {
      const q = parseFloat(form.QualityAndGoals.value) || 0;
      const d = parseFloat(form.DurationScore.value) || 0;
      const t = parseFloat(form.TextCompleteness.value) || 0;
      const p = parseFloat(form.PresentationScore.value) || 0;
      const total = (q * 0.60) + (d * 0.15) + (t * 0.15) + (p * 0.10);
      if (finalOutput) finalOutput.textContent = total.toFixed(2);
    }

    form.addEventListener('input', updateFinal);
    updateFinal();

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (statusBox) {
        statusBox.style.color = '';
        statusBox.textContent = 'Saving...';
      }

      try {
        const fd = new FormData(form);
        const res = await fetch(form.action || 'insert_grade.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });

        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch { data = { ok: res.ok, message: text }; }

        if (!res.ok || data.ok === false) {
          if (statusBox) {
            statusBox.style.color = '#b00020';
            statusBox.textContent = data.message || `Save failed (${res.status}).`;
          }
          return;
        }

        if (statusBox) {
          statusBox.style.color = '#0a7a3e';
          statusBox.textContent = data.message || 'Grade saved successfully.';
        }

      } catch (err) {
        if (statusBox) {
          statusBox.style.color = '#b00020';
          statusBox.textContent = 'Network error. Please try again.';
        }
        console.error(err);
      }
    });
  };

  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();