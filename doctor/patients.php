<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('doctor');
$doc = my_doctor((int)$u['user_id']);
if (!$doc) redirect('doctor/dashboard.php');

$rows = fetch_all("SELECT p.user_id, p.name, p.phone, p.gender, p.dob, COUNT(a.appointment_id) visits,
                          MAX(a.appointment_date) last_visit,
                          (SELECT COUNT(*) FROM health_records r WHERE r.user_id=p.user_id AND r.doctor_id=a.doctor_id) records
                   FROM appointments a JOIN users p ON p.user_id=a.user_id
                   WHERE a.doctor_id=? AND a.status IN ('approved','completed')
                   GROUP BY p.user_id, p.name, p.phone, p.gender, p.dob, a.doctor_id ORDER BY last_visit DESC", [$doc['doctor_id']]);

layout_top('My Patients', true);
?>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Patient</th><th>Age / gender</th><th>Phone</th><th>Visits</th><th>Last visit</th><th>Records</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $p): ?>
    <tr><td><?= e($p['name']) ?></td><td><?= age_from($p['dob']) ?> / <?= e(ucfirst((string)$p['gender']) ?: '-') ?></td><td><?= e($p['phone']) ?></td>
        <td><?= $p['visits'] ?></td><td><?= fmt_date($p['last_visit']) ?></td><td><?= $p['records'] ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-teal" href="<?= url('doctor/records.php?patient=' . $p['user_id']) ?>">Records</a></td></tr>
  <?php endforeach; if (!$rows) echo empty_row(7, 'No patients yet.'); ?>
  </tbody></table></div></div>
<?php layout_bottom(true); ?>
