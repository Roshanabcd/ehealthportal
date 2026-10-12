<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('doctor');
$doc = my_doctor((int)$u['user_id']);
if (!$doc) redirect('doctor/dashboard.php');
$did = (int)$doc['doctor_id'];

if (is_post()) {
    csrf_check();
    $id = (int)post('id'); $action = post('action');
    $map = ['approve' => 'approved', 'reject' => 'rejected', 'complete' => 'completed', 'cancel' => 'cancelled'];
    $owned = fetch_one('SELECT appointment_id FROM appointments WHERE appointment_id=? AND doctor_id=?', [$id, $did]); // only own appointments
    if ($owned && isset($map[$action]) && set_appointment_status($id, $map[$action], post('note'), $u)) {
        flash('success', 'Appointment marked as ' . $map[$action] . '. The citizen has been notified.');
        if ($action === 'complete') redirect('doctor/record_add.php?appointment_id=' . $id);
    } else flash('error', 'That action is not allowed for this appointment.');
    redirect('doctor/appointments.php?' . http_build_query(['status' => get('status'), 'date' => get('date')]));
}

$status = get('status'); $date = get('date');
$where = 'a.doctor_id=?'; $params = [$did];
if (in_array($status, ['pending', 'approved', 'completed', 'cancelled', 'rejected'], true)) { $where .= ' AND a.status=?'; $params[] = $status; }
if (valid_date($date)) { $where .= ' AND a.appointment_date=?'; $params[] = $date; }
$total = (int)fetch_val("SELECT COUNT(*) FROM appointments a WHERE $where", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT a.*, p.name patient_name, p.phone patient_phone, s.service_name,
                          (SELECT record_id FROM health_records r WHERE r.appointment_id=a.appointment_id LIMIT 1) record_id
                   FROM appointments a JOIN users p ON p.user_id=a.user_id JOIN health_services s ON s.service_id=a.service_id
                   WHERE $where ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $per OFFSET $off", $params);

layout_top('My Appointments', true);
?>
<div class="card">
  <div class="card-header"><form method="get" class="row g-2 align-items-center">
    <div class="col-auto"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All statuses</option>
      <?php foreach (['pending', 'approved', 'completed', 'cancelled', 'rejected'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-auto"><input type="date" name="date" value="<?= e($date) ?>" class="form-control form-control-sm" onchange="this.form.submit()"></div>
    <div class="col-auto"><a href="appointments.php" class="btn btn-sm btn-outline-secondary">Reset</a></div>
  </form></div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Date &amp; time</th><th>Patient</th><th>Service / reason</th><th>Status</th><th style="min-width:230px">Action</th></tr></thead><tbody>
    <?php foreach ($rows as $a): ?>
      <tr>
        <td>#<?= $a['appointment_id'] ?></td>
        <td><?= fmt_date($a['appointment_date']) ?><br><small class="text-muted"><?= fmt_time($a['appointment_time']) ?></small></td>
        <td><?= e($a['patient_name']) ?><br><small class="text-muted"><?= e($a['patient_phone']) ?></small></td>
        <td><?= e($a['service_name']) ?><br><small class="text-muted"><?= e($a['reason']) ?></small></td>
        <td><?= status_badge($a['status']) ?></td>
        <td>
          <?php if (in_array($a['status'], ['pending', 'approved'], true)): ?>
          <form method="post" class="d-flex flex-wrap gap-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['appointment_id'] ?>">
            <input name="note" class="form-control form-control-sm mb-1" placeholder="Remarks (optional)" maxlength="300">
            <?php if ($a['status'] === 'pending'): ?>
              <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
              <button name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button>
            <?php else: ?>
              <button name="action" value="complete" class="btn btn-sm btn-teal">Complete + record</button>
              <button name="action" value="cancel" class="btn btn-sm btn-outline-danger">Cancel</button>
            <?php endif; ?>
          </form>
          <?php elseif ($a['status'] === 'completed'): ?>
            <?php if ($a['record_id']): ?><a href="<?= url('record.php?id=' . $a['record_id']) ?>" class="btn btn-sm btn-outline-teal">View record</a>
            <?php else: ?><a href="<?= url('doctor/record_add.php?appointment_id=' . $a['appointment_id']) ?>" class="btn btn-sm btn-teal">Add record</a><?php endif; ?>
          <?php else: ?><span class="text-muted small"><?= e($a['admin_note']) ?></span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; if (!$rows) echo empty_row(6, 'No appointments found.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
