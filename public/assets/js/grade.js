
(function () {
  const form = document.getElementById('gradeForm');
  const finalOutput = document.getElementById('finalScore');

  if (!form || !finalOutput) return; 

  function updateFinal() {
    const q = parseFloat(form.QualityAndGoals.value) || 0;
    const d = parseFloat(form.DurationScore.value) || 0;
    const t = parseFloat(form.TextCompleteness.value) || 0;
    const p = parseFloat(form.PresentationScore.value) || 0;

    const total = (q * 0.60) + (d * 0.15) + (t * 0.15) + (p * 0.10);
    finalOutput.textContent = total.toFixed(2);
  }

  form.addEventListener('input', updateFinal);
  updateFinal(); 
})();