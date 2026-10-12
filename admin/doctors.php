<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

if (is_post()) {
    csrf_check();
    $id = (int)post('id'); $d = fetch_one('SELECT * FROM doctors WHERE doctor_id=?', [$id]);
    if ($d && post('action') === 'toggle') {
        $new = $d['status'] === 'active' ? 'inactive' : 'active';
        q('UPDATE doctors SET status=? WHERE doctor_id=?', [$new, $id]);
        if ($d['user_id']) q('UPDATE users SET status=? WHERE user_id=?', [$new, $d['user_id']]); // inactive doctor cannot log in either
        log_activity((int)$u['user_id'], "Doctor #$id set $new");
        flash('success', "{$d['name']} is now $new.");
    } elseif ($d && post('action') === 'delete') {
        if ((int)fetch_val('SELECT COUNT(*) FROM appointments WHERE doctor_id=?', [$id]) || (int)fetch_val('SELECT COUNT(*) FROM health_records WHERE doctor_id=?', [$id]))
            flash('error', 'This doctor has appointments or records, so deletion is blocked. Deactivate the doctor instead.');
        else {
            q('DELETE FROM doctors WHERE doctor_id=?', [$id]);
            if ($d['user_id']) q('DELETE FROM users WHERE user_id=?', [$d['user_id']]);
            log_activity((int)$u['user_id'], "Deleted doctor #$id"); flash('success', 'Doctor deleted.');
        }
    }
    redirect('admin/doctors.php');
}
$search = get('q');
$rows = fetch_all('SELECT d.*, u.email, (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id=d.doctor_id) appts FROM doctors d
                   LEFT JOIN users u ON u.user_id=d.user_id WHERE d.name LIKE ? OR d.specialization LIKE ? OR d.department LIKE ? ORDER BY d.name',
                  ["%$search%", "%$search%", "%$search%"]);

layout_top('Doctors / Service Providers', true);
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
    <form method="get" class="d-flex gap-2"><input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search doctor, specialization..."><button class="btn btn-sm btn-teal"><i class="bi bi-search"></i></button></form>
    <a href="<?= url('admin/doctor_form.php') ?>" class="btn btn-sm btn-teal"><i class="bi bi-plus-lg me-1"></i>Add doctor</a>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Doctor</th><th>Specialization</th><th>Department</th><th>Login email</th><th>Appts</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $d): ?>
      <tr><td><strong><?= e($d['name']) ?></strong><br><small class="text-muted"><?= e($d['contact']) ?></small></td><td><?= e($d['specialization']) ?></td>
        <td><?= e($d['department']) ?></td><td class="small"><?= e($d['email'] ?: '-') ?></td><td><?= $d['appts'] ?></td><td><?= status_badge($d['status']) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/doctor_form.php?id=' . $d['doctor_id']) ?>">Edit</a>
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['doctor_id'] ?>"><input type="hidden" name="action" value="toggle">
            <button class="btn btn-sm btn-outline-warning"><?= $d['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
          <form method="post" class="d-inline" data-confirm="Delete this doctor and their login account?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['doctor_id'] ?>"><input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
    <?php endforeach; if (!$rows) echo empty_row(7); ?>
    </tbody></table></div>
</div>
<?php layout_bottom(true); ?>
