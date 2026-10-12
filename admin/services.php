<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

if (is_post()) {
    csrf_check();
    $id = (int)post('id'); $s = fetch_one('SELECT * FROM health_services WHERE service_id=?', [$id]);
    if ($s && post('action') === 'toggle') {
        $new = $s['status'] === 'active' ? 'inactive' : 'active';
        q('UPDATE health_services SET status=? WHERE service_id=?', [$new, $id]);
        log_activity((int)$u['user_id'], "Service #$id set $new");
        flash('success', "Service \"{$s['service_name']}\" is now $new.");
    } elseif ($s && post('action') === 'delete') {
        if ((int)fetch_val('SELECT COUNT(*) FROM appointments WHERE service_id=?', [$id]) > 0)
            flash('error', 'This service already has appointments, so it cannot be deleted. Deactivate it instead.');
        else { q('DELETE FROM health_services WHERE service_id=?', [$id]); log_activity((int)$u['user_id'], "Deleted service #$id"); flash('success', 'Service deleted.'); }
    }
    redirect('admin/services.php');
}
$search = get('q');
$rows = fetch_all('SELECT s.*, (SELECT COUNT(*) FROM appointments a WHERE a.service_id=s.service_id) appts FROM health_services s
                   WHERE s.service_name LIKE ? OR s.department LIKE ? ORDER BY s.service_name', ["%$search%", "%$search%"]);

layout_top('Health Services', true);
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
    <form method="get" class="d-flex gap-2"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search service / department"><button class="btn btn-sm btn-teal"><i class="bi bi-search"></i></button></form>
    <a href="<?= url('admin/service_form.php') ?>" class="btn btn-sm btn-teal"><i class="bi bi-plus-lg me-1"></i>Add service</a>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Service</th><th>Department</th><th>Appointments</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $s): ?>
      <tr><td><strong><?= e($s['service_name']) ?></strong><br><small class="text-muted"><?= e(mb_strimwidth((string)$s['description'], 0, 80, '...')) ?></small></td>
        <td><?= e($s['department']) ?></td><td><?= $s['appts'] ?></td><td><?= status_badge($s['status']) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/service_form.php?id=' . $s['service_id']) ?>">Edit</a>
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $s['service_id'] ?>"><input type="hidden" name="action" value="toggle">
            <button class="btn btn-sm btn-outline-warning"><?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
          <form method="post" class="d-inline" data-confirm="Delete this service permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $s['service_id'] ?>"><input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger">Delete</button></form>
        </td></tr>
    <?php endforeach; if (!$rows) echo empty_row(5); ?>
    </tbody></table></div>
</div>
<?php layout_bottom(true); ?>
