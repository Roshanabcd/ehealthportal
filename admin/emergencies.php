<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    $status = post('status');
    if (in_array($status, ['pending', 'dispatched', 'resolved', 'cancelled'], true)) {
        q('UPDATE emergencies SET status = ? WHERE emergency_id = ?', [$status, $id]);
        flash('success', "Emergency #$id status updated to $status.");
    }
    redirect('admin/emergencies.php?' . http_build_query(array_filter(['status' => get('status'), 'q' => get('q'), 'page' => get('page')])));
}

$status = get('status'); $search = get('q');
$where = '1=1'; $params = [];
if (in_array($status, ['pending', 'dispatched', 'resolved', 'cancelled'], true)) { $where .= ' AND status=?'; $params[] = $status; }
if ($search !== '') { $where .= ' AND (requester_name LIKE ? OR contact_number LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$base = "FROM emergencies WHERE $where";
$total = (int)fetch_val("SELECT COUNT(*) $base", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT * $base ORDER BY created_at DESC LIMIT $per OFFSET $off", $params);

layout_top('Manage Emergencies', true);
?>
<div class="card border-danger">
  <div class="card-header bg-danger text-white"><h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Emergency Requests</h5></div>
  <div class="card-header"><form method="get" class="row g-2">
    <div class="col-md-4"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search name / phone"></div>
    <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>
      <?php foreach (['pending', 'dispatched', 'resolved', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3 d-flex gap-1"><button class="btn btn-sm btn-danger flex-fill">Filter</button><a href="emergencies.php" class="btn btn-sm btn-outline-secondary">Reset</a></div>
  </form></div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Date/Time</th><th>Requester</th><th>Type & Location</th><th>Status</th><th style="min-width:180px">Action</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['status'] === 'pending' ? 'table-warning' : '' ?>">
        <td>#<?= $r['emergency_id'] ?></td>
        <td><?= fmt_date(substr($r['created_at'], 0, 10)) ?><br><small class="text-muted"><?= fmt_time(substr($r['created_at'], 11, 8)) ?></small></td>
        <td><strong><?= e($r['requester_name']) ?></strong><br><a href="tel:<?= e($r['contact_number']) ?>" class="text-decoration-none"><i class="bi bi-telephone-fill me-1"></i><?= e($r['contact_number']) ?></a></td>
        <td><span class="badge bg-danger"><?= e($r['emergency_type']) ?></span><br><small><?= e($r['location']) ?></small>
            <?php if ($r['description']): ?><div class="small mt-1 text-muted border-top pt-1"><?= e($r['description']) ?></div><?php endif; ?>
        </td>
        <td>
            <?php
            $bg = match($r['status']) { 'pending' => 'bg-warning text-dark', 'dispatched' => 'bg-info text-dark', 'resolved' => 'bg-success', default => 'bg-secondary' };
            echo "<span class='badge $bg'>".ucfirst($r['status'])."</span>";
            ?>
        </td>
        <td>
          <form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['emergency_id'] ?>">
            <select name="status" class="form-select form-select-sm w-auto">
                <?php foreach (['pending', 'dispatched', 'resolved', 'cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-teal">Update</button>
          </form>
        </td></tr>
    <?php endforeach; if (!$rows) echo empty_row(6, 'No emergency requests found.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> request(s)</small><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
