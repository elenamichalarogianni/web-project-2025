const typeSel = document.getElementById('presentation_type');
const locInput = document.getElementById('location_or_link');
const locLabel = document.getElementById('loc_label');

function updateLocLabel() {
  if (typeSel.value === 'Online') {
    locLabel.textContent = 'Meeting Link';
    if (!locInput.placeholder.includes('http')) {
      locInput.placeholder = 'https://zoom.us/..., https://meet.google.com/...';
    }
  } else {
    locLabel.textContent = 'Location';
    if (locInput.placeholder.includes('http')) {
      locInput.placeholder = 'e.g., Room B12, Building A';
    }
  }
}
typeSel.addEventListener('change', updateLocLabel);
updateLocLabel();