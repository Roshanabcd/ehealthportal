<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('citizen');

$total = (int)fetch_val('SELECT COUNT(*) FROM health_records WHERE user_id=?', [$u['user_id']]);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT r.*, d.name doctor_name, d.specialization FROM health_records r JOIN doctors d ON d.doctor_id=r.doctor_id
                   WHERE r.user_id=? ORDER BY r.record_date DESC, r.record_id DESC LIMIT $per OFFSET $off", [$u['user_id']]);

layout_top('My Health Records', true);
?>
<div class="alert alert-info small"><i class="bi bi-shield-lock me-1"></i>Only you, your treating doctors and the portal administrator can see these records.</div>
<div class="card">
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Record</th><th>Date</th><th>Diagnosis</th><th>Doctor</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td>HR-<?= str_pad((string)$r['record_id'], 5, '0', STR_PAD_LEFT) ?></td><td><?= fmt_date($r['record_date']) ?></td>
          <td><?= e($r['diagnosis']) ?></td><td><?= e($r['doctor_name']) ?><br><small class="text-muted"><?= e($r['specialization']) ?></small></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-teal" href="<?= url('record.php?id=' . $r['record_id']) ?>"><i class="bi bi-eye"></i> View / Print</a></td></tr>
    <?php endforeach; if (!$rows) echo empty_row(5, 'No health records have been added for you yet.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
