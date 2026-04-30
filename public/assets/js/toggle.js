document.addEventListener("DOMContentLoaded", () => {
  const toggles = Array.from(document.querySelectorAll(".js-grade-toggle"));
  if (toggles.length === 0) {
    
    return;
  }

  toggles.forEach((el) => {
    if (!el) return; 

    const thesisId = Number(el.dataset.thesisId);
    if (!Number.isFinite(thesisId)) {
      console.warn("Missing/invalid data-thesis-id on:", el);
      return;
    }

    el.addEventListener("change", async () => {
      try {
        const res = await fetch("toggle_thesis.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify({ thesis_id: thesisId, enabled: el.checked ? 1 : 0 }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.ok !== true) throw new Error(data.error || `HTTP ${res.status}`);
      } catch (e) {
        el.checked = !el.checked; 
        console.error(e);
        alert("Couldn’t update. Reverting toggle.");
      }
    });
  });
});