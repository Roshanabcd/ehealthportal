<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

if (is_post()) {
    csrf_check();
    $id = (int)post('id'); $t = fetch_one('SELECT * FROM users WHERE user_id=?', [$id]);
    if (!$t) flash('error', 'User not found.');
    elseif ($id === (int)$u['user_id']) flash('error', 'You cannot change your own account here. Use My Profile.');
    elseif (post('action') === 'toggle') {
        $new = $t['status'] === 'active' ? 'inactive' : 'active';
        q('UPDATE users SET status=?, failed_logins=0, locked_until=NULL WHERE user_id=?', [$new, $id]);
        if ($t['role'] === 'doctor') q('UPDATE doctors SET status=? WHERE user_id=?', [$new, $id]);
        log_activity((int)$u['user_id'], "User #$id set $new");
        flash('success', "{$t['name']} is now $new.");
    } elseif (post('action') === 'reset') {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'; $tmp = '';
        for ($i = 0; $i < 10; $i++) $tmp .= $chars[random_int(0, strlen($chars) - 1)];
        $tmp .= random_int(10, 99); // guarantees letters + digits
        q('UPDATE users SET password=?, failed_logins=0, locked_until=NULL WHERE user_id=?', [password_hash($tmp, PASSWORD_DEFAULT), $id]);
        log_activity((int)$u['user_id'], "Reset password of user #$id");
        flash('success', "Temporary password for {$t['name']}: $tmp  (share it securely; ask them to change it after login)");
    }
    redirect('admin/users.php?' . http_build_query(array_filter(['role' => get('role'), 'q' => get('q'), 'page' => get('page')])));
}

$role = get('role'); $search = get('q');
$where = '1=1'; $params = [];
if (in_array($role, ['admin', 'doctor', 'citizen'], true)) { $where .= ' AND role=?'; $params[] = $role; }
if ($search !== '') { $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)'; array_push($params, "%$search%", "%$search%", "%$search%"); }
$total = (int)fetch_val("SELECT COUNT(*) FROM users WHERE $where", $params);
[$off, $per, $page, $pages] = paginate($total, 10);
$rows = fetch_all("SELECT * FROM users WHERE $where ORDER BY created_at DESC, user_id DESC LIMIT $per OFFSET $off", $params);

layout_top('User Management', true);
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
    <form method="get" class="d-flex gap-2">
      <input name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search name, email, phone">
      <select name="role" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All roles</option>
        <?php foreach (['citizen', 'doctor', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select>
      <button class="btn btn-sm btn-teal"><i class="bi bi-search"></i></button>
    </form>
    <a href="<?= url('admin/user_form.php') ?>" class="btn btn-sm btn-teal"><i class="bi bi-plus-lg me-1"></i>Add user</a>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Registered</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td class="small"><?= e($r['email']) ?></td><td><?= e($r['phone']) ?></td>
        <td><span class="badge bg-info text-dark"><?= e(ucfirst($r['role'])) ?></span></td><td><?= status_badge($r['status']) ?></td><td class="small"><?= fmt_date($r['created_at']) ?></td>
        <td class="text-end text-nowrap">
          <?php if ((int)$r['user_id'] !== (int)$u['user_id']): ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?= url($r['role'] === 'doctor' ? 'admin/doctors.php?q=' . urlencode($r['name']) : 'admin/user_form.php?id=' . $r['user_id']) ?>">Edit</a>
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['user_id'] ?>"><input type="hidden" name="action" value="toggle">
            <button class="btn btn-sm btn-outline-warning"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
          <form method="post" class="d-inline" data-confirm="Generate a new temporary password for this user?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['user_id'] ?>"><input type="hidden" name="action" value="reset">
            <button class="btn btn-sm btn-outline-primary">Reset password</button></form>
          <?php else: ?><span class="text-muted small">(you)</span><?php endif; ?>
        </td></tr>
    <?php endforeach; if (!$rows) echo empty_row(7, 'No users found.'); ?>
    </tbody></table></div>
  <div class="card-footer bg-white d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> user(s)</small><?= pagination_html($page, $pages) ?></div>
</div>
<?php layout_bottom(true); ?>
