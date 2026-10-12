<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$search = get('q');
$where = '1=1'; $params = [];
if ($search !== '') { $where .= ' AND (l.action LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR l.ip_address LIKE ?)'; array_push($params, "%$search%", "%$search%", "%$search%", "%$search%"); }
$total = (int)fetch_val("SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.user_id=l.user_id WHERE $where", $params);
[$off, $per, $page, $pages] = paginate($total, 20);
$rows = fetch_all("SELECT l.*, u.name, u.role FROM activity_logs l LEFT JOIN users u ON u.user_id=l.user_id WHERE $where
                   ORDER BY l.log_id DESC LIMIT $per OFFSET $off", $params);

layout_top('Activity Log', true);
?>
<div class="card">
  <div class="card-header"><form method="get" class="d-flex gap-2"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search user, action or IP"><button class="btn btn-sm btn-teal"><i class="bi bi-search"></i></button></form></div>
  <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>IP</th></tr></thead><tbody>
    <?php foreach ($rows as $l): ?>
      <tr><td class="text-nowrap small"><?= fmt_datetime($l['created_at']) ?></td><td><?= e($l['name'] ?? 'Guest / deleted') ?></td>
        <td><?= $l['role'] ? '<span class="badge bg-info text-dark">' . e(ucfirst($l['role'])) . '</span>' : '-' ?></td><td><?= e($l['action']) ?></td><td class="small"><?= e($l['ip_address']) ?></td></tr>
    <?php endforeach; if (!$rows) echo empty_row(5, 'No activity recorded yet.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
