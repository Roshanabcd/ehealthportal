<?php
require_once __DIR__ . '/includes/layout.php';
$u = require_role('admin', 'doctor', 'citizen');
$panel = true;

if (is_post()) {
    csrf_check();
    if (post('action') === 'profile') {
        $name = post('name'); $phone = post('phone'); $gender = post('gender'); $dob = post('dob'); $addr = post('address');
        $err = [];
        if (mb_strlen($name) < 3) $err[] = 'Enter your full name.';
        if (!valid_phone($phone)) $err[] = 'Enter a valid phone number.';
        if ($gender !== '' && !in_array($gender, ['male', 'female', 'other'], true)) $err[] = 'Invalid gender.';
        if ($dob !== '' && !valid_date($dob)) $err[] = 'Invalid date of birth.';
        if ($err) { foreach ($err as $x) flash('error', $x); }
        else {
            q('UPDATE users SET name=?,phone=?,gender=?,dob=?,address=? WHERE user_id=?',
              [$name, $phone, $gender ?: null, $dob ?: null, $addr ?: null, $u['user_id']]);
            if ($u['role'] === 'doctor') q('UPDATE doctors SET name=?, contact=? WHERE user_id=?', [$name, $phone, $u['user_id']]);
            log_activity((int)$u['user_id'], 'Updated profile');
            flash('success', 'Profile updated.');
        }
    } elseif (post('action') === 'password') {
        $row = fetch_one('SELECT password FROM users WHERE user_id=?', [$u['user_id']]);
        $new = (string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''), $row['password'])) flash('error', 'Current password is incorrect.');
        elseif ($e2 = strong_password($new)) flash('error', $e2);
        elseif ($new !== ($_POST['new_password2'] ?? '')) flash('error', 'New passwords do not match.');
        else {
            q('UPDATE users SET password=? WHERE user_id=?', [password_hash($new, PASSWORD_DEFAULT), $u['user_id']]);
            session_regenerate_id(true);
            log_activity((int)$u['user_id'], 'Changed password');
            flash('success', 'Password changed successfully.');
        }
    }
    redirect('profile.php');
}
$u = fetch_one('SELECT * FROM users WHERE user_id=?', [$u['user_id']]);
layout_top('My Profile', true);
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-header">Personal information</div><div class="card-body">
      <form method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <div class="col-md-6"><label class="form-label">Full name</label><input name="name" class="form-control" value="<?= e($u['name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email (cannot be changed)</label><input class="form-control" value="<?= e($u['email']) ?>" disabled></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($u['phone']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Gender</label>
          <select name="gender" class="form-select"><option value="">-</option>
            <?php foreach (['male', 'female', 'other'] as $g): ?><option value="<?= $g ?>" <?= $u['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-4"><label class="form-label">Date of birth</label><input type="date" name="dob" class="form-control" value="<?= e($u['dob']) ?>"></div>
        <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($u['address']) ?>"></div>
        <div class="col-12"><button class="btn btn-teal">Save changes</button></div>
      </form>
    </div></div>
  </div>
  <div class="col-lg-5">
    <div class="card"><div class="card-header">Change password</div><div class="card-body">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">New password</label><input type="password" name="new_password" class="form-control" required minlength="8"></div>
        <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="new_password2" class="form-control" required></div>
        <button class="btn btn-outline-teal">Update password</button>
      </form>
    </div></div>
  </div>
</div>
<?php layout_bottom(true); ?>
