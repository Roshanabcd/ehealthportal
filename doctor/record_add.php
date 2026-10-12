<?php
// Create (or edit) a health record. Doctors can only write records for patients who booked with them.
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('doctor');
$doc = my_doctor((int)$u['user_id']);
if (!$doc) redirect('doctor/dashboard.php');
$did = (int)$doc['doctor_id'];

$editing = null;
if (get('id') !== '') {
    $editing = fetch_one('SELECT * FROM health_records WHERE record_id=? AND doctor_id=?', [(int)get('id'), $did]);
    if (!$editing) { flash('error', 'Record not found.'); redirect('doctor/records.php'); }
}
$appt = null;
if (!$editing && get('appointment_id') !== '') {
    $appt = fetch_one("SELECT * FROM appointments WHERE appointment_id=? AND doctor_id=? AND status IN ('approved','completed')", [(int)get('appointment_id'), $did]);
    if (!$appt) { flash('error', 'Appointment not found or not ready for a record.'); redirect('doctor/appointments.php'); }
}

$patients = fetch_all("SELECT DISTINCT p.user_id, p.name FROM appointments a JOIN users p ON p.user_id=a.user_id
                       WHERE a.doctor_id=? AND a.status IN ('approved','completed') ORDER BY p.name", [$did]);
$f = $editing ?: ['user_id' => $appt['user_id'] ?? 0, 'appointment_id' => $appt['appointment_id'] ?? null, 'diagnosis' => '', 'prescription' => '',
                  'record_date' => $appt['appointment_date'] ?? date('Y-m-d'), 'notes' => ''];
if ($f['record_date'] > date('Y-m-d')) $f['record_date'] = date('Y-m-d');

if (is_post()) {
    csrf_check();
    $f['user_id'] = (int)post('user_id'); $f['diagnosis'] = post('diagnosis'); $f['prescription'] = post('prescription');
    $f['record_date'] = post('record_date'); $f['notes'] = post('notes');
    $apptId = $editing ? $editing['appointment_id'] : ((int)post('appointment_id') ?: null);
    $allowed = in_array($f['user_id'], array_map('intval', array_column($patients, 'user_id')), true);
    if ($apptId && !$editing && !fetch_one('SELECT 1 x FROM appointments WHERE appointment_id=? AND doctor_id=? AND user_id=?', [$apptId, $did, $f['user_id']])) $apptId = null;

    if (!$allowed && !$editing)                             flash('error', 'Select a valid patient (one who has an appointment with you).');
    elseif (mb_strlen($f['diagnosis']) < 3 || mb_strlen($f['diagnosis']) > 500) flash('error', 'Diagnosis is required (3-500 characters).');
    elseif (!valid_date($f['record_date']) || $f['record_date'] > date('Y-m-d')) flash('error', 'Enter a valid record date (not in the future).');
    elseif ($editing) {
        q('UPDATE health_records SET diagnosis=?,prescription=?,record_date=?,notes=? WHERE record_id=?',
          [$f['diagnosis'], $f['prescription'] ?: null, $f['record_date'], $f['notes'] ?: null, $editing['record_id']]);
        log_activity((int)$u['user_id'], 'Updated health record #' . $editing['record_id']);
        flash('success', 'Health record updated.'); redirect('doctor/records.php');
    } else {
        q('INSERT INTO health_records (user_id,doctor_id,appointment_id,diagnosis,prescription,record_date,notes) VALUES (?,?,?,?,?,?,?)',
          [$f['user_id'], $did, $apptId, $f['diagnosis'], $f['prescription'] ?: null, $f['record_date'], $f['notes'] ?: null]);
        $rid = (int)db()->lastInsertId();
        notify($f['user_id'], 'New health record added', $doc['name'] . ' added a health record for your visit on ' . fmt_date($f['record_date']) . '.');
        log_activity((int)$u['user_id'], "Created health record #$rid");
        flash('success', 'Health record saved.'); redirect('doctor/records.php');
    }
}

layout_top($editing ? 'Edit Health Record' : 'Add Health Record', true);
?>
<div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-body">
  <form method="post" class="row g-3"><?= csrf_field() ?>
    <input type="hidden" name="appointment_id" value="<?= e($f['appointment_id'] ?? '') ?>">
    <div class="col-md-8"><label class="form-label">Patient <span class="text-danger">*</span></label>
      <?php if ($editing): $pn = fetch_val('SELECT name FROM users WHERE user_id=?', [$editing['user_id']]); ?>
        <input class="form-control" value="<?= e($pn) ?>" disabled>
      <?php else: ?>
        <select name="user_id" class="form-select" required><option value="">-- Select patient --</option>
          <?php foreach ($patients as $p): ?><option value="<?= $p['user_id'] ?>" <?= (int)$f['user_id'] === (int)$p['user_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
      <?php endif; ?></div>
    <div class="col-md-4"><label class="form-label">Record date <span class="text-danger">*</span></label>
      <input type="date" name="record_date" class="form-control" required max="<?= date('Y-m-d') ?>" value="<?= e($f['record_date']) ?>"></div>
    <div class="col-12"><label class="form-label">Diagnosis <span class="text-danger">*</span></label>
      <input name="diagnosis" class="form-control" required maxlength="500" value="<?= e($f['diagnosis']) ?>"></div>
    <div class="col-12"><label class="form-label">Prescription / treatment</label>
      <textarea name="prescription" rows="4" class="form-control"><?= e($f['prescription']) ?></textarea></div>
    <div class="col-12"><label class="form-label">Notes</label>
      <textarea name="notes" rows="3" class="form-control"><?= e($f['notes']) ?></textarea></div>
    <div class="col-12"><button class="btn btn-teal"><i class="bi bi-save me-1"></i>Save record</button>
      <a href="<?= url('doctor/records.php') ?>" class="btn btn-outline-secondary">Cancel</a></div>
  </form>
</div></div></div></div>
<?php layout_bottom(true); ?>
