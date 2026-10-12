<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('citizen');

if (is_post()) {
    csrf_check();
    $a = fetch_one('SELECT * FROM appointments WHERE appointment_id=? AND user_id=?', [(int)post('id'), $u['user_id']]); // ownership check
    if ($a && post('action') === 'cancel' && set_appointment_status((int)$a['appointment_id'], 'cancelled', 'Cancelled by citizen', $u))
        flash('success', 'Appointment cancelled.');
    else flash('error', 'This appointment can no longer be cancelled.');
    redirect('citizen/appointments.php');
}

$status = get('status');
$where = 'a.user_id=?'; $params = [$u['user_id']];
if (in_array($status, ['pending', 'approved', 'completed', 'cancelled', 'rejected'], true)) { $where .= ' AND a.status=?'; $params[] = $status; }
$total = (int)fetch_val("SELECT COUNT(*) FROM appointments a WHERE $where", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT a.*, s.service_name, d.name doctor_name, d.specialization,
                          (SELECT record_id FROM health_records r WHERE r.appointment_id=a.appointment_id LIMIT 1) record_id
                   FROM appointments a JOIN health_services s ON s.service_id=a.service_id JOIN doctors d ON d.doctor_id=a.doctor_id
                   WHERE $where ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $per OFFSET $off", $params);

layout_top('My Appointments', true);
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <span>Appointment &amp; request status</span>
    <form method="get" class="d-flex gap-2">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'completed', 'cancelled', 'rejected'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
      <a href="<?= url('citizen/book.php') ?>" class="btn btn-sm btn-teal text-nowrap"><i class="bi bi-plus-lg"></i> New</a>
    </form>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Date &amp; time</th><th>Service</th><th>Doctor</th><th>Status</th><th>Remarks</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $a): ?>
      <tr>
        <td>#<?= $a['appointment_id'] ?></td>
        <td><?= fmt_date($a['appointment_date']) ?><br><small class="text-muted"><?= fmt_time($a['appointment_time']) ?></small></td>
        <td><?= e($a['service_name']) ?><br><small class="text-muted"><?= e(mb_strimwidth((string)$a['reason'], 0, 40, '...')) ?></small></td>
        <td><?= e($a['doctor_name']) ?><br><small class="text-muted"><?= e($a['specialization']) ?></small></td>
        <td><?= status_badge($a['status']) ?></td>
        <td class="small"><?= e($a['admin_note'] ?: '-') ?></td>
        <td class="text-end text-nowrap">
          <?php if ($a['record_id']): ?><a class="btn btn-sm btn-outline-teal" href="<?= url('record.php?id=' . $a['record_id']) ?>"><i class="bi bi-file-medical"></i> Record</a><?php endif; ?>
          <?php if (in_array($a['status'], ['pending', 'approved'], true)): ?>
            <form method="post" class="d-inline" data-confirm="Cancel this appointment?"><?= csrf_field() ?>
              <input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $a['appointment_id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; if (!$rows) echo empty_row(7, 'You have no appointments yet.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
