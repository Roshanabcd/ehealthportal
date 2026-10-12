<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$from = valid_date(get('from')) ? get('from') : date('Y-m-01', strtotime('-5 months'));
$to   = valid_date(get('to'))   ? get('to')   : date('Y-m-d', strtotime('+60 days'));

// CSV export of appointments in the selected period
if (get('export') === 'csv') {
    $rows = fetch_all("SELECT a.appointment_id, p.name patient, s.service_name, d.name doctor, a.appointment_date, a.appointment_time, a.status, a.reason
                       FROM appointments a JOIN users p ON p.user_id=a.user_id JOIN health_services s ON s.service_id=a.service_id JOIN doctors d ON d.doctor_id=a.doctor_id
                       WHERE a.appointment_date BETWEEN ? AND ? ORDER BY a.appointment_date, a.appointment_time", [$from, $to]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="appointments_' . $from . '_to_' . $to . '.csv"');
    $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Citizen', 'Service', 'Doctor', 'Date', 'Time', 'Status', 'Reason']);
    foreach ($rows as $r) fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v, $r)); // neutralise CSV formula injection
    exit;
}

$range = [$from, $to];
$byMonth = fetch_all("SELECT DATE_FORMAT(appointment_date,'%Y-%m') m, COUNT(*) total, SUM(status='completed') done, SUM(status IN ('cancelled','rejected')) lost
                      FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY m ORDER BY m", $range);
$byService = fetch_all("SELECT s.service_name, s.department, COUNT(*) c FROM appointments a JOIN health_services s ON s.service_id=a.service_id
                        WHERE a.appointment_date BETWEEN ? AND ? GROUP BY s.service_id, s.service_name, s.department ORDER BY c DESC", $range);
$byDoctor = fetch_all("SELECT d.name, d.specialization, COUNT(*) c, SUM(a.status='completed') done FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id
                       WHERE a.appointment_date BETWEEN ? AND ? GROUP BY d.doctor_id, d.name, d.specialization ORDER BY c DESC", $range);
$newUsers = (int)fetch_val("SELECT COUNT(*) FROM users WHERE role='citizen' AND DATE(created_at) BETWEEN ? AND ?", $range);

layout_top('Reports', true);
?>
<form method="get" class="card card-body mb-3 no-print"><div class="row g-2 align-items-end">
  <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm"></div>
  <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm"></div>
  <div class="col-md-6 d-flex gap-2">
    <button class="btn btn-sm btn-teal">Generate</button>
    <a class="btn btn-sm btn-outline-teal" href="?<?= e(http_build_query(['from' => $from, 'to' => $to, 'export' => 'csv'])) ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
  </div></div></form>
<p class="text-muted">Period: <strong><?= fmt_date($from) ?></strong> to <strong><?= fmt_date($to) ?></strong> &bull; New citizen registrations: <strong><?= $newUsers ?></strong></p>

<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header">Appointments per month</div>
    <table class="table mb-0"><thead><tr><th>Month</th><th>Total</th><th>Completed</th><th>Cancelled / rejected</th></tr></thead><tbody>
    <?php foreach ($byMonth as $m): ?><tr><td><?= date('M Y', strtotime($m['m'] . '-01')) ?></td><td><?= $m['total'] ?></td><td><?= $m['done'] ?></td><td><?= $m['lost'] ?></td></tr><?php endforeach;
    if (!$byMonth) echo empty_row(4); ?></tbody></table></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header">Appointments per service</div>
    <table class="table mb-0"><thead><tr><th>Service</th><th>Department</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($byService as $s): ?><tr><td><?= e($s['service_name']) ?></td><td><?= e($s['department']) ?></td><td><?= $s['c'] ?></td></tr><?php endforeach;
    if (!$byService) echo empty_row(3); ?></tbody></table></div></div>
  <div class="col-12"><div class="card"><div class="card-header">Doctor workload</div>
    <table class="table mb-0"><thead><tr><th>Doctor</th><th>Specialization</th><th>Appointments</th><th>Completed</th></tr></thead><tbody>
    <?php foreach ($byDoctor as $d): ?><tr><td><?= e($d['name']) ?></td><td><?= e($d['specialization']) ?></td><td><?= $d['c'] ?></td><td><?= $d['done'] ?></td></tr><?php endforeach;
    if (!$byDoctor) echo empty_row(4); ?></tbody></table></div></div>
</div>
<?php layout_bottom(true); ?>
