<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('citizen');
$id = (int)$u['user_id']; $today = date('Y-m-d');

$upcoming = (int)fetch_val("SELECT COUNT(*) FROM appointments WHERE user_id=? AND status IN ('pending','approved') AND appointment_date>=?", [$id, $today]);
$pending  = (int)fetch_val("SELECT COUNT(*) FROM appointments WHERE user_id=? AND status='pending'", [$id]);
$done     = (int)fetch_val("SELECT COUNT(*) FROM appointments WHERE user_id=? AND status='completed'", [$id]);
$records  = (int)fetch_val('SELECT COUNT(*) FROM health_records WHERE user_id=?', [$id]);

$next = fetch_all("SELECT a.*, s.service_name, d.name doctor_name FROM appointments a
                   JOIN health_services s ON s.service_id=a.service_id JOIN doctors d ON d.doctor_id=a.doctor_id
                   WHERE a.user_id=? AND a.status IN ('pending','approved') AND a.appointment_date>=?
                   ORDER BY a.appointment_date, a.appointment_time LIMIT 5", [$id, $today]);
$notes = fetch_all('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC, notification_id DESC LIMIT 4', [$id]);

layout_top('Citizen Dashboard', true);
?>
<div class="alert alert-light border d-flex align-items-center">
  <i class="bi bi-person-heart fs-3 text-teal me-3"></i>
  <div><strong>Namaste, <?= e($u['name']) ?>!</strong><div class="small text-muted">Book appointments, track their status and view your health records from one place.</div></div>
  <a href="<?= url('citizen/book.php') ?>" class="btn btn-teal ms-auto"><i class="bi bi-calendar-plus me-1"></i>Book appointment</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><?= kpi('calendar-event', $upcoming, 'Upcoming appointments') ?></div>
  <div class="col-6 col-lg-3"><?= kpi('hourglass-split', $pending, 'Awaiting approval') ?></div>
  <div class="col-6 col-lg-3"><?= kpi('check2-circle', $done, 'Completed visits') ?></div>
  <div class="col-6 col-lg-3"><?= kpi('file-medical', $records, 'Health records') ?></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100"><div class="card-header d-flex justify-content-between"><span>Upcoming appointments</span><a href="<?= url('citizen/appointments.php') ?>" class="small">View all</a></div>
      <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Date</th><th>Time</th><th>Service</th><th>Doctor</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($next as $a): ?>
          <tr><td><?= fmt_date($a['appointment_date']) ?></td><td><?= fmt_time($a['appointment_time']) ?></td>
              <td><?= e($a['service_name']) ?></td><td><?= e($a['doctor_name']) ?></td><td><?= status_badge($a['status']) ?></td></tr>
        <?php endforeach; if (!$next) echo empty_row(5, 'No upcoming appointments.'); ?>
        </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header d-flex justify-content-between"><span>Latest notifications</span><a href="<?= url('notifications.php') ?>" class="small">View all</a></div>
      <div class="list-group list-group-flush">
        <?php foreach ($notes as $n): ?>
          <div class="list-group-item <?= $n['is_read'] ? '' : 'notif-unread' ?>"><strong class="small"><?= e($n['title']) ?></strong>
            <div class="small text-muted"><?= e(mb_strimwidth($n['message'], 0, 90, '...')) ?></div></div>
        <?php endforeach; if (!$notes) echo '<div class="list-group-item text-muted small">No notifications yet.</div>'; ?>
      </div>
    </div>
  </div>
</div>
<?php layout_bottom(true); ?>
