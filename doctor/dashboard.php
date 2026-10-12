<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('doctor');
$doc = my_doctor((int)$u['user_id']);
if (!$doc) { flash('error', 'No doctor profile is linked to your account. Contact the administrator.'); redirect('profile.php'); }
$did = (int)$doc['doctor_id']; $today = date('Y-m-d');

$todayCnt = (int)fetch_val("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND status IN ('pending','approved','completed')", [$did, $today]);
$pending  = (int)fetch_val("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='pending'", [$did]);
$patients = (int)fetch_val('SELECT COUNT(DISTINCT user_id) FROM appointments WHERE doctor_id=?', [$did]);
$records  = (int)fetch_val('SELECT COUNT(*) FROM health_records WHERE doctor_id=?', [$did]);

$schedule = fetch_all("SELECT a.*, p.name patient_name, s.service_name FROM appointments a JOIN users p ON p.user_id=a.user_id
                       JOIN health_services s ON s.service_id=a.service_id
                       WHERE a.doctor_id=? AND a.appointment_date=? AND a.status IN ('pending','approved','completed') ORDER BY a.appointment_time", [$did, $today]);
$waiting = fetch_all("SELECT a.*, p.name patient_name, s.service_name FROM appointments a JOIN users p ON p.user_id=a.user_id
                      JOIN health_services s ON s.service_id=a.service_id
                      WHERE a.doctor_id=? AND a.status='pending' AND a.appointment_date>=? ORDER BY a.appointment_date, a.appointment_time LIMIT 6", [$did, $today]);

layout_top('Doctor Dashboard', true);
?>
<div class="alert alert-light border d-flex align-items-center">
  <i class="bi bi-person-badge fs-3 text-teal me-3"></i>
  <div><strong><?= e($doc['name']) ?></strong><div class="small text-muted"><?= e($doc['specialization']) ?> &bull; <?= e($doc['department']) ?></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><?= kpi('calendar-day', $todayCnt, "Today's appointments") ?></div>
  <div class="col-6 col-lg-3"><?= kpi('hourglass-split', $pending, 'Waiting for approval') ?></div>
  <div class="col-6 col-lg-3"><?= kpi('people', $patients, 'Patients served') ?></div>
  <div class="col-6 col-lg-3"><?= kpi('file-medical', $records, 'Records created') ?></div>
</div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header">Today's schedule (<?= fmt_date($today) ?>)</div>
    <div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Time</th><th>Patient</th><th>Service</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($schedule as $a): ?>
      <tr><td><?= fmt_time($a['appointment_time']) ?></td><td><?= e($a['patient_name']) ?></td><td><?= e($a['service_name']) ?></td><td><?= status_badge($a['status']) ?></td></tr>
    <?php endforeach; if (!$schedule) echo empty_row(4, 'No appointments scheduled for today.'); ?>
    </tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header d-flex justify-content-between"><span>Requests waiting for your decision</span><a href="<?= url('doctor/appointments.php?status=pending') ?>" class="small">Manage</a></div>
    <div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Date</th><th>Patient</th><th>Service</th></tr></thead><tbody>
    <?php foreach ($waiting as $a): ?>
      <tr><td><?= fmt_date($a['appointment_date']) ?><br><small class="text-muted"><?= fmt_time($a['appointment_time']) ?></small></td><td><?= e($a['patient_name']) ?></td><td><?= e($a['service_name']) ?></td></tr>
    <?php endforeach; if (!$waiting) echo empty_row(3, 'No pending requests.'); ?>
    </tbody></table></div></div></div>
</div>
<?php layout_bottom(true); ?>
