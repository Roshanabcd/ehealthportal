<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('citizen');

$services = fetch_all("SELECT service_id, service_name, department FROM health_services WHERE status='active' ORDER BY service_name");
$in = ['service_id' => (int)get('service_id'), 'doctor_id' => 0, 'date' => '', 'time' => '', 'reason' => ''];

if (is_post()) {
    csrf_check();
    $in = ['service_id' => (int)post('service_id'), 'doctor_id' => (int)post('doctor_id'), 'date' => post('appointment_date'),
           'time' => post('appointment_time'), 'reason' => post('reason')];
    $err = null;
    $okDoctor = in_array($in['doctor_id'], array_map('intval', array_column(doctors_for_service($in['service_id']), 'doctor_id')), true);

    if (!$okDoctor)                                   $err = 'Please select a valid service and a doctor available for it.';
    elseif (!valid_date($in['date']) || $in['date'] < date('Y-m-d')) $err = 'Please choose a valid appointment date (today or later).';
    elseif ($in['date'] > date('Y-m-d', strtotime('+' . MAX_BOOKING_DAYS . ' days'))) $err = 'You can book up to ' . MAX_BOOKING_DAYS . ' days in advance.';
    elseif ((int)date('w', strtotime($in['date'])) === OFF_DAY) $err = 'Appointments are not available on Saturdays.';
    elseif (!preg_match('/^\d{2}:\d{2}$/', $in['time'])) $err = 'Please select a time slot.';
    elseif (mb_strlen($in['reason']) < 5 || mb_strlen($in['reason']) > 500) $err = 'Please describe the reason for your visit (5-500 characters).';

    if (!$err) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Lock the doctor row so two citizens cannot grab the same slot at the same moment
            q('SELECT doctor_id FROM doctors WHERE doctor_id=? FOR UPDATE', [$in['doctor_id']])->fetch();
            if (!in_array($in['time'], free_slots($in['doctor_id'], $in['date']), true))
                throw new RuntimeException('Sorry, that time slot is no longer available. Please choose another.');
            if (fetch_val("SELECT COUNT(*) FROM appointments WHERE user_id=? AND appointment_date=? AND appointment_time=? AND status IN ('pending','approved')",
                          [$u['user_id'], $in['date'], $in['time'] . ':00']))
                throw new RuntimeException('You already have another appointment at that date and time.');

            q('INSERT INTO appointments (user_id,service_id,doctor_id,appointment_date,appointment_time,reason,status) VALUES (?,?,?,?,?,?,?)',
              [$u['user_id'], $in['service_id'], $in['doctor_id'], $in['date'], $in['time'] . ':00', $in['reason'], 'pending']);
            $aid = (int)$pdo->lastInsertId();
            $pdo->commit();

            $doc = fetch_one('SELECT name,user_id FROM doctors WHERE doctor_id=?', [$in['doctor_id']]);
            notify((int)$u['user_id'], 'Appointment request received', 'Your request for ' . fmt_date($in['date']) . ' at ' . fmt_time($in['time']) . ' with ' . $doc['name'] . ' is awaiting approval.');
            if ($doc['user_id']) notify((int)$doc['user_id'], 'New appointment request', $u['name'] . ' requested an appointment on ' . fmt_date($in['date']) . ' at ' . fmt_time($in['time']) . '.');
            log_activity((int)$u['user_id'], "Booked appointment #$aid");
            flash('success', 'Appointment request submitted. You will be notified once it is approved.');
            redirect('citizen/appointments.php');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $ex->getMessage();
        }
    }
    if ($err) flash('error', $err);
}

layout_top('Book an Appointment', true);
?>
<div class="row justify-content-center"><div class="col-xl-8">
<div class="card"><div class="card-header"><i class="bi bi-calendar-plus me-1"></i>New appointment request</div><div class="card-body">
  <form method="post" class="row g-3"><?= csrf_field() ?>
    <div class="col-md-6"><label class="form-label">Health service <span class="text-danger">*</span></label>
      <select name="service_id" id="service_id" class="form-select" required>
        <option value="">-- Select service --</option>
        <?php foreach ($services as $s): ?><option value="<?= $s['service_id'] ?>" <?= $in['service_id'] === (int)$s['service_id'] ? 'selected' : '' ?>><?= e($s['service_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label">Doctor <span class="text-danger">*</span></label>
      <select name="doctor_id" id="doctor_id" class="form-select" data-selected="<?= $in['doctor_id'] ?: '' ?>" required>
        <option value="">Select a service first</option></select></div>
    <div class="col-md-6"><label class="form-label">Preferred date <span class="text-danger">*</span></label>
      <input type="date" name="appointment_date" id="appointment_date" class="form-control" required value="<?= e($in['date']) ?>"
             min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . MAX_BOOKING_DAYS . ' days')) ?>">
      <div class="form-text">Saturdays are closed (weekly holiday).</div></div>
    <div class="col-12"><label class="form-label">Available time slots <span class="text-danger">*</span></label>
      <div id="slots" data-selected="<?= e($in['time']) ?>"><span class="text-muted small">Choose doctor and date to see free time slots.</span></div></div>
    <div class="col-12"><label class="form-label">Reason for visit <span class="text-danger">*</span></label>
      <textarea name="reason" class="form-control" rows="3" maxlength="500" required placeholder="Briefly describe your symptoms or the service you need"><?= e($in['reason']) ?></textarea></div>
    <div class="col-12"><button class="btn btn-teal"><i class="bi bi-send me-1"></i>Submit request</button>
      <a href="<?= url('citizen/appointments.php') ?>" class="btn btn-outline-secondary">Cancel</a></div>
  </form>
</div></div>
</div></div>
<?php layout_bottom(true); ?>
