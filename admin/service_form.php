<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$id = (int)get('id');
$f = ['service_name' => '', 'description' => '', 'department' => '', 'status' => 'active'];
if ($id) {
    $row = fetch_one('SELECT * FROM health_services WHERE service_id=?', [$id]);
    if (!$row) { flash('error', 'Service not found.'); redirect('admin/services.php'); }
    $f = $row;
}
$depts = array_column(fetch_all('SELECT DISTINCT department FROM doctors UNION SELECT DISTINCT department FROM health_services ORDER BY department'), 'department');

if (is_post()) {
    csrf_check();
    $f = ['service_name' => post('service_name'), 'description' => post('description'), 'department' => post('department'),
          'status' => post('status') === 'inactive' ? 'inactive' : 'active'];
    if (mb_strlen($f['service_name']) < 3 || mb_strlen($f['service_name']) > 120) flash('error', 'Service name must be 3-120 characters.');
    elseif ($f['department'] === '' || mb_strlen($f['department']) > 80) flash('error', 'Department is required (max 80 characters).');
    elseif ($id) {
        q('UPDATE health_services SET service_name=?,description=?,department=?,status=? WHERE service_id=?', [$f['service_name'], $f['description'], $f['department'], $f['status'], $id]);
        log_activity((int)$u['user_id'], "Updated service #$id"); flash('success', 'Service updated.'); redirect('admin/services.php');
    } else {
        q('INSERT INTO health_services (service_name,description,department,status) VALUES (?,?,?,?)', [$f['service_name'], $f['description'], $f['department'], $f['status']]);
        log_activity((int)$u['user_id'], 'Added service ' . $f['service_name']); flash('success', 'Service added.'); redirect('admin/services.php');
    }
}
layout_top($id ? 'Edit Health Service' : 'Add Health Service', true);
?>
<div class="row justify-content-center"><div class="col-xl-7"><div class="card"><div class="card-body">
  <form method="post" class="row g-3"><?= csrf_field() ?>
    <div class="col-12"><label class="form-label">Service name *</label><input name="service_name" class="form-control" required maxlength="120" value="<?= e($f['service_name']) ?>"></div>
    <div class="col-md-8"><label class="form-label">Department * <small class="text-muted">(doctors in this department are offered for the service)</small></label>
      <input name="department" list="deptList" class="form-control" required maxlength="80" value="<?= e($f['department']) ?>">
      <datalist id="deptList"><?php foreach ($depts as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">
      <option value="active" <?= $f['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $f['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="4" class="form-control"><?= e($f['description']) ?></textarea></div>
    <div class="col-12"><button class="btn btn-teal"><i class="bi bi-save me-1"></i>Save</button> <a href="<?= url('admin/services.php') ?>" class="btn btn-outline-secondary">Cancel</a></div>
  </form></div></div></div></div>
<?php layout_bottom(true); ?>
