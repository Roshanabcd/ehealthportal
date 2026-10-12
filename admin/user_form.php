<?php
// Add / edit a citizen or administrator account (doctors are managed on the Doctors page).
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$id = (int)get('id');
$f = ['name' => '', 'email' => '', 'phone' => '', 'role' => 'citizen', 'gender' => '', 'dob' => '', 'address' => ''];
$row = null;
if ($id) {
    $row = fetch_one('SELECT * FROM users WHERE user_id=?', [$id]);
    if (!$row) { flash('error', 'User not found.'); redirect('admin/users.php'); }
    if ($row['role'] === 'doctor') redirect('admin/doctors.php');
    $f = array_intersect_key($row, $f);
}
if (is_post()) {
    csrf_check();
    $f = ['name' => post('name'), 'email' => strtolower(post('email')), 'phone' => post('phone'), 'role' => post('role') === 'admin' ? 'admin' : 'citizen',
          'gender' => post('gender'), 'dob' => post('dob'), 'address' => post('address')];
    $pass = (string)($_POST['password'] ?? ''); $err = null;
    if (mb_strlen($f['name']) < 3) $err = 'Enter the full name.';
    elseif (!valid_phone($f['phone'])) $err = 'Enter a valid phone number.';
    elseif (!$row && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email address.';
    elseif (!$row && fetch_val('SELECT COUNT(*) FROM users WHERE email=?', [$f['email']])) $err = 'That email is already registered.';
    elseif ($f['gender'] !== '' && !in_array($f['gender'], ['male', 'female', 'other'], true)) $err = 'Invalid gender.';
    elseif ($f['dob'] !== '' && (!valid_date($f['dob']) || $f['dob'] > date('Y-m-d'))) $err = 'Invalid date of birth.';
    elseif ((!$row || $pass !== '') && ($e2 = strong_password($pass))) $err = $e2;
    elseif ($row && $id === (int)$u['user_id'] && $f['role'] !== $row['role']) $err = 'You cannot change your own role.';

    if ($err) flash('error', $err);
    elseif ($row) {
        q('UPDATE users SET name=?,phone=?,role=?,gender=?,dob=?,address=? WHERE user_id=?', [$f['name'], $f['phone'], $f['role'], $f['gender'] ?: null, $f['dob'] ?: null, $f['address'] ?: null, $id]);
        if ($pass !== '') q('UPDATE users SET password=? WHERE user_id=?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
        log_activity((int)$u['user_id'], "Updated user #$id"); flash('success', 'User updated.'); redirect('admin/users.php');
    } else {
        q('INSERT INTO users (name,email,password,role,phone,gender,dob,address) VALUES (?,?,?,?,?,?,?,?)',
          [$f['name'], $f['email'], password_hash($pass, PASSWORD_DEFAULT), $f['role'], $f['phone'], $f['gender'] ?: null, $f['dob'] ?: null, $f['address'] ?: null]);
        log_activity((int)$u['user_id'], 'Added user ' . $f['email']); flash('success', 'User created.'); redirect('admin/users.php');
    }
}
layout_top($id ? 'Edit User' : 'Add User', true);
?>
<div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-body">
  <form method="post" class="row g-3"><?= csrf_field() ?>
    <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" class="form-control" required value="<?= e($f['name']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Email <?= $row ? '(cannot be changed)' : '*' ?></label><input type="email" name="email" class="form-control" <?= $row ? 'disabled' : 'required' ?> value="<?= e($f['email']) ?>"></div>
    <div class="col-md-4"><label class="form-label">Phone *</label><input name="phone" class="form-control" required value="<?= e($f['phone']) ?>"></div>
    <div class="col-md-4"><label class="form-label">Role</label><select name="role" class="form-select">
      <option value="citizen" <?= $f['role'] === 'citizen' ? 'selected' : '' ?>>Citizen</option><option value="admin" <?= $f['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option></select></div>
    <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">-</option>
      <?php foreach (['male', 'female', 'other'] as $g): ?><option value="<?= $g ?>" <?= $f['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Date of birth</label><input type="date" name="dob" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($f['dob']) ?>"></div>
    <div class="col-md-8"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($f['address']) ?>"></div>
    <div class="col-md-6"><label class="form-label"><?= $row ? 'New password (leave blank to keep)' : 'Password *' ?></label>
      <input type="password" name="password" class="form-control" minlength="8" <?= $row ? '' : 'required' ?> autocomplete="new-password"></div>
    <div class="col-12"><button class="btn btn-teal"><i class="bi bi-save me-1"></i>Save</button> <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary">Cancel</a></div>
  </form></div></div></div></div>
<?php layout_bottom(true); ?>
