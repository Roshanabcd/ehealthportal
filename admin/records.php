<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$search = get('q');
$where = '1=1'; $params = [];
if ($search !== '') { $where .= ' AND (p.name LIKE ? OR d.name LIKE ? OR r.diagnosis LIKE ?)'; array_push($params, "%$search%", "%$search%", "%$search%"); }
$base = "FROM health_records r JOIN users p ON p.user_id=r.user_id JOIN doctors d ON d.doctor_id=r.doctor_id WHERE $where";
$total = (int)fetch_val("SELECT COUNT(*) $base", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT r.*, p.name patient_name, d.name doctor_name $base ORDER BY r.record_date DESC, r.record_id DESC LIMIT $per OFFSET $off", $params);

layout_top('Health Records (Administrator view)', true);
?>
<div class="card">
  <div class="card-header"><form method="get" class="d-flex gap-2"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search patient, doctor or diagnosis"><button class="btn btn-sm btn-teal"><i class="bi bi-search"></i></button></form></div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Record</th><th>Date</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td>HR-<?= str_pad((string)$r['record_id'], 5, '0', STR_PAD_LEFT) ?></td><td><?= fmt_date($r['record_date']) ?></td><td><?= e($r['patient_name']) ?></td>
          <td><?= e($r['doctor_name']) ?></td><td><?= e($r['diagnosis']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-teal" href="<?= url('record.php?id=' . $r['record_id']) ?>">View</a></td></tr>
    <?php endforeach; if (!$rows) echo empty_row(6); ?>
    </tbody></table></div>
  <div class="card-footer bg-white d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> record(s) &mdash; every access is logged</small><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
