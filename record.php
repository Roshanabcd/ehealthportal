<?php
// Shared, printable view of ONE health record. Access is checked per role (see can_view_record()).
require_once __DIR__ . '/includes/layout.php';
$u = require_role('admin', 'doctor', 'citizen');

$r = fetch_one("SELECT r.*, p.name patient_name, p.email patient_email, p.phone patient_phone, p.gender, p.dob, p.address,
                       d.name doctor_name, d.specialization, d.department
                FROM health_records r
                JOIN users p ON p.user_id=r.user_id
                JOIN doctors d ON d.doctor_id=r.doctor_id
                WHERE r.record_id=?", [(int)get('id')]);
if (!$r || !can_view_record($u, $r)) {
    http_response_code(403);
    flash('error', 'Health record not found or you are not authorized to view it.');
    redirect(home_for($u['role']));
}
log_activity((int)$u['user_id'], 'Viewed health record #' . $r['record_id']);

layout_top('Health Record #' . $r['record_id'], true);
?>
<div class="mb-3 no-print">
  <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
  <button onclick="window.print()" class="btn btn-teal btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
</div>
<div class="record-paper">
  <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
    <div><h2 class="h4 mb-0 text-teal">E-Health Government Service Portal</h2><div class="text-muted small">Official Health Record</div></div>
    <div class="text-end small"><strong>Record No:</strong> HR-<?= str_pad((string)$r['record_id'], 5, '0', STR_PAD_LEFT) ?><br><strong>Date:</strong> <?= fmt_date($r['record_date']) ?></div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <h6 class="text-muted text-uppercase small">Patient</h6>
      <div class="fw-semibold"><?= e($r['patient_name']) ?></div>
      <div class="small">Age: <?= age_from($r['dob']) ?> &bull; Gender: <?= e(ucfirst((string)$r['gender']) ?: '-') ?></div>
      <div class="small"><?= e($r['patient_phone']) ?> &bull; <?= e($r['patient_email']) ?></div>
      <div class="small"><?= e($r['address']) ?></div>
    </div>
    <div class="col-md-6">
      <h6 class="text-muted text-uppercase small">Attending doctor</h6>
      <div class="fw-semibold"><?= e($r['doctor_name']) ?></div>
      <div class="small"><?= e($r['specialization']) ?> &bull; <?= e($r['department']) ?></div>
    </div>
  </div>
  <h6 class="text-muted text-uppercase small">Diagnosis</h6>
  <p class="fs-5"><?= e($r['diagnosis']) ?></p>
  <h6 class="text-muted text-uppercase small">Prescription / Treatment</h6>
  <p><?= nl2br(e($r['prescription'] ?: '-')) ?></p>
  <h6 class="text-muted text-uppercase small">Doctor's notes</h6>
  <p><?= nl2br(e($r['notes'] ?: '-')) ?></p>
  <hr>
  <div class="small text-muted">This is a computer-generated record from an academic prototype and is not a legal medical document.</div>
</div>
<?php layout_bottom(true); ?>
