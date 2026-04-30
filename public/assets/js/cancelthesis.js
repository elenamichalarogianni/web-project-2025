
function cancelThesis(thesisID, status) {
  fetch("cancel_thesis.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ thesis_id: thesisID, new_status: status }),
    credentials: "same-origin"
  })
  .then(r => r.text())
  .then(txt => {
    if (txt.includes("επιτυχώς")) {
      const li = document.getElementById("thesis-" + thesisID);
      if (li) li.remove();
    } else {
      alert("Σφάλμα: " + txt);
    }
  })
  .catch(() => alert("Πρόβλημα σύνδεσης με τον server."));
}
