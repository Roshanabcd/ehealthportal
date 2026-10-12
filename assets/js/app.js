(function () {
  // Mobile sidebar toggle
  var t = document.getElementById('sidebarToggle'), s = document.getElementById('sidebar');
  if (t && s) t.addEventListener('click', function () { s.classList.toggle('open'); });

  // Auto-hide flash alerts
  setTimeout(function () {
    document.querySelectorAll('.alert-success.show').forEach(function (a) {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(a).close();
    });
  }, 5000);

  // Confirm dialogs: <form data-confirm="Are you sure?">
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  // ---------- Appointment booking widget ----------
  var svc = document.getElementById('service_id'), doc = document.getElementById('doctor_id'),
      date = document.getElementById('appointment_date'), box = document.getElementById('slots');
  if (svc && doc && date && box) {
    var selectedTime = box.dataset.selected || '';
    function loadDoctors() {
      doc.innerHTML = '<option value="">Loading...</option>';
      if (!svc.value) { doc.innerHTML = '<option value="">Select a service first</option>'; box.innerHTML = ''; return; }
      fetch(window.BASE_URL + '/api/doctors.php?service_id=' + encodeURIComponent(svc.value))
        .then(function (r) { return r.json(); })
        .then(function (list) {
          if (!list.length) { doc.innerHTML = '<option value="">No doctors available for this service</option>'; box.innerHTML = ''; return; }
          doc.innerHTML = '<option value="">-- Select doctor --</option>' + list.map(function (d) {
            return '<option value="' + d.doctor_id + '">' + d.name + ' (' + d.specialization + ')</option>';
          }).join('');
          if (doc.dataset.selected) { doc.value = doc.dataset.selected; doc.dataset.selected = ''; loadSlots(); }
        });
    }
    function loadSlots() {
      box.innerHTML = '';
      if (!doc.value || !date.value) { box.innerHTML = '<span class="text-muted small">Choose doctor and date to see free time slots.</span>'; return; }
      box.innerHTML = '<span class="text-muted small">Loading slots...</span>';
      fetch(window.BASE_URL + '/api/slots.php?doctor_id=' + encodeURIComponent(doc.value) + '&date=' + encodeURIComponent(date.value))
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.slots.length) { box.innerHTML = '<span class="text-danger small">' + (res.message || 'No free slots on this date.') + '</span>'; return; }
          box.innerHTML = '<div class="slot-grid">' + res.slots.map(function (s) {
            return '<span><input type="radio" name="appointment_time" id="t' + s.value.replace(':', '') + '" value="' + s.value + '"' +
              (s.value === selectedTime ? ' checked' : '') + ' required><label for="t' + s.value.replace(':', '') + '">' + s.label + '</label></span>';
          }).join('') + '</div>';
        });
    }
    svc.addEventListener('change', function () { selectedTime = ''; loadDoctors(); });
    doc.addEventListener('change', function () { selectedTime = ''; loadSlots(); });
    date.addEventListener('change', function () { selectedTime = ''; loadSlots(); });
    if (svc.value) loadDoctors();
  }
})();
