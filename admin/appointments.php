<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

if (is_post()) {
    csrf_check();
    $map = ['approve' => 'approved', 'reject' => 'rejected', 'complete' => 'completed', 'cancel' => 'cancelled'];
    $act = post('action');
    if (isset($map[$act]) && set_appointment_status((int)post('id'), $map[$act], post('note'), $u)) flash('success', 'Appointment marked as ' . $map[$act] . '.');
    else flash('error', 'That action is not allowed for this appointment.');
    redirect('admin/appointments.php?' . http_build_query(array_filter(['status' => get('status'), 'doctor' => get('doctor'), 'date' => get('date'), 'q' => get('q'), 'page' => get('page')])));
}

$status = get('status'); $doctor = (int)get('doctor'); $date = get('date'); $search = get('q');
$where = '1=1'; $params = [];
if (in_array($status, ['pending', 'approved', 'completed', 'cancelled', 'rejected'], true)) { $where .= ' AND a.status=?'; $params[] = $status; }
if ($doctor) { $where .= ' AND a.doctor_id=?'; $params[] = $doctor; }
if (valid_date($date)) { $where .= ' AND a.appointment_date=?'; $params[] = $date; }
if ($search !== '') { $where .= ' AND (p.name LIKE ? OR p.email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$base = "FROM appointments a JOIN users p ON p.user_id=a.user_id JOIN health_services s ON s.service_id=a.service_id JOIN doctors d ON d.doctor_id=a.doctor_id WHERE $where";
$total = (int)fetch_val("SELECT COUNT(*) $base", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT a.*, p.name patient_name, p.phone patient_phone, s.service_name, d.name doctor_name $base
                   ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $per OFFSET $off", $params);
$doctors = fetch_all('SELECT doctor_id, name FROM doctors ORDER BY name');

layout_top('Manage Appointments', true);
?>
<div class="card">
  <div class="card-header"><form method="get" class="row g-2">
    <div class="col-md-3"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search citizen name / email"></div>
    <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>
      <?php foreach (['pending', 'approved', 'completed', 'cancelled', 'rejected'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><select name="doctor" class="form-select form-select-sm"><option value="">All doctors</option>
      <?php foreach ($doctors as $d): ?><option value="<?= $d['doctor_id'] ?>" <?= $doctor === (int)$d['doctor_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input type="date" name="date" value="<?= e($date) ?>" class="form-control form-control-sm"></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-sm btn-teal flex-fill">Filter</button><a href="appointments.php" class="btn btn-sm btn-outline-secondary">Reset</a></div>
  </form></div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Citizen</th><th>Service</th><th>Doctor</th><th>Date &amp; time</th><th>Status</th><th style="min-width:230px">Action</th></tr></thead><tbody>
    <?php foreach ($rows as $a): ?>
      <tr><td>#<?= $a['appointment_id'] ?></td>
        <td><?= e($a['patient_name']) ?><br><small class="text-muted"><?= e($a['patient_phone']) ?></small></td>
        <td><?= e($a['service_name']) ?><br><small class="text-muted"><?= e(mb_strimwidth((string)$a['reason'], 0, 40, '...')) ?></small></td>
        <td><?= e($a['doctor_name']) ?></td>
        <td><?= fmt_date($a['appointment_date']) ?><br><small class="text-muted"><?= fmt_time($a['appointment_time']) ?></small></td>
        <td><?= status_badge($a['status']) ?></td>
        <td>
          <?php if (isset(appointment_transitions()[$a['status']])): ?>
          <form method="post" class="d-flex flex-wrap gap-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['appointment_id'] ?>">
            <input name="note" class="form-control form-control-sm mb-1" placeholder="Remarks (optional)" maxlength="300">
            <?php if ($a['status'] === 'pending'): ?>
              <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
              <button name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button>
            <?php else: ?>
              <button name="action" value="complete" class="btn btn-sm btn-teal">Complete</button>
              <button name="action" value="cancel" class="btn btn-sm btn-outline-danger">Cancel</button>
            <?php endif; ?>
          </form>
          <?php else: ?><span class="text-muted small"><?= e($a['admin_note'] ?: '-') ?></span><?php endif; ?>
        </td></tr>
    <?php endforeach; if (!$rows) echo empty_row(7, 'No appointments match the filters.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> result(s)</small><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
