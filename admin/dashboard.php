<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$kpis = [
    ['people',        (int)fetch_val("SELECT COUNT(*) FROM users WHERE role='citizen'"),               'Registered citizens'],
    ['person-badge',  (int)fetch_val("SELECT COUNT(*) FROM doctors WHERE status='active'"),            'Active doctors'],
    ['heart-pulse',   (int)fetch_val("SELECT COUNT(*) FROM health_services WHERE status='active'"),    'Active services'],
    ['calendar-check',(int)fetch_val('SELECT COUNT(*) FROM appointments'),                              'Total appointments'],
    ['hourglass-split',(int)fetch_val("SELECT COUNT(*) FROM appointments WHERE status='pending'"),      'Pending requests'],
    ['exclamation-triangle-fill', (int)fetch_val("SELECT COUNT(*) FROM emergencies WHERE status='pending'"), 'Active emergencies'],
];
$byStatus = fetch_all('SELECT status, COUNT(*) c FROM appointments GROUP BY status');
$byService = fetch_all('SELECT s.service_name, COUNT(a.appointment_id) c FROM health_services s LEFT JOIN appointments a ON a.service_id=s.service_id GROUP BY s.service_id, s.service_name ORDER BY c DESC LIMIT 6');
$maxSvc = max(1, ...array_map('intval', array_column($byService, 'c') ?: [1]));
$recent = fetch_all("SELECT a.*, p.name patient_name, s.service_name, d.name doctor_name FROM appointments a
                     JOIN users p ON p.user_id=a.user_id JOIN health_services s ON s.service_id=a.service_id JOIN doctors d ON d.doctor_id=a.doctor_id
                     ORDER BY a.created_at DESC, a.appointment_id DESC LIMIT 6");

layout_top('Administrator Dashboard', true);
?>
<div class="row g-3 mb-3">
  <?php foreach ($kpis as [$ic, $v, $l]): ?><div class="col-6 col-lg-4 col-xl-2"><?= kpi($ic, $v, $l) ?></div><?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-5"><div class="card h-100"><div class="card-header">Appointments by status</div>
    <div class="card-body"><div style="max-width:300px;margin:auto"><canvas id="statusChart"></canvas></div></div></div></div>
  <div class="col-lg-7"><div class="card h-100"><div class="card-header">Most requested services</div><div class="card-body">
    <?php foreach ($byService as $s): ?>
      <div class="bar-row"><div class="name text-truncate" title="<?= e($s['service_name']) ?>"><?= e($s['service_name']) ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= round($s['c'] / $maxSvc * 100) ?>%"></div></div><strong><?= (int)$s['c'] ?></strong></div>
    <?php endforeach; ?>
  </div></div></div>
</div>
<div class="card"><div class="card-header d-flex justify-content-between"><span>Latest appointment requests</span><a href="<?= url('admin/appointments.php') ?>" class="small">Manage all</a></div>
  <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
    <thead><tr><th>#</th><th>Citizen</th><th>Service</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($recent as $a): ?>
      <tr><td>#<?= $a['appointment_id'] ?></td><td><?= e($a['patient_name']) ?></td><td><?= e($a['service_name']) ?></td><td><?= e($a['doctor_name']) ?></td>
          <td><?= fmt_date($a['appointment_date']) ?> <small class="text-muted"><?= fmt_time($a['appointment_time']) ?></small></td><td><?= status_badge($a['status']) ?></td></tr>
    <?php endforeach; if (!$recent) echo empty_row(6); ?>
    </tbody></table></div></div>

<script src="<?= asset('vendor/chart/chart.umd.js') ?>"></script>
<script>
(function () {
  var data = <?= json_encode($byStatus) ?>;
  var colors = {pending:'#f4b400', approved:'#0d6efd', completed:'#198754', cancelled:'#6c757d', rejected:'#dc3545'};
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: data.map(function (d) { return d.status.charAt(0).toUpperCase() + d.status.slice(1); }),
            datasets: [{ data: data.map(function (d) { return +d.c; }), backgroundColor: data.map(function (d) { return colors[d.status]; }) }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
})();
</script>
<?php layout_bottom(true); ?>
