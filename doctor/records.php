<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('doctor');
$doc = my_doctor((int)$u['user_id']);
if (!$doc) redirect('doctor/dashboard.php');
$did = (int)$doc['doctor_id'];

$search = get('q');
$where = 'r.doctor_id=?'; $params = [$did];
if (ctype_digit(get('patient'))) { $where .= ' AND r.user_id=?'; $params[] = (int)get('patient'); }
if ($search !== '') { $where .= ' AND (p.name LIKE ? OR r.diagnosis LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$total = (int)fetch_val("SELECT COUNT(*) FROM health_records r JOIN users p ON p.user_id=r.user_id WHERE $where", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT r.*, p.name patient_name FROM health_records r JOIN users p ON p.user_id=r.user_id
                   WHERE $where ORDER BY r.record_date DESC, r.record_id DESC LIMIT $per OFFSET $off", $params);

layout_top('Health Records', true);
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
    <form method="get" class="d-flex gap-2"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search patient or diagnosis">
      <button class="btn btn-sm btn-teal"><i class="bi bi-search"></i></button></form>
    <a href="<?= url('doctor/record_add.php') ?>" class="btn btn-sm btn-teal"><i class="bi bi-plus-lg me-1"></i>Add record</a>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Record</th><th>Date</th><th>Patient</th><th>Diagnosis</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td>HR-<?= str_pad((string)$r['record_id'], 5, '0', STR_PAD_LEFT) ?></td><td><?= fmt_date($r['record_date']) ?></td>
          <td><?= e($r['patient_name']) ?></td><td><?= e($r['diagnosis']) ?></td>
          <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-teal" href="<?= url('record.php?id=' . $r['record_id']) ?>">View</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('doctor/record_add.php?id=' . $r['record_id']) ?>">Edit</a></td></tr>
    <?php endforeach; if (!$rows) echo empty_row(5, 'No records found.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
