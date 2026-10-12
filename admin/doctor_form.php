<?php
// Add / edit a doctor. A new doctor automatically gets a login account (role = doctor).
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

$id = (int)get('id');
$f = ['name' => '', 'email' => '', 'contact' => '', 'specialization' => '', 'department' => '', 'status' => 'active'];
$doc = null;
if ($id) {
    $doc = fetch_one('SELECT d.*, u.email FROM doctors d LEFT JOIN users u ON u.user_id=d.user_id WHERE d.doctor_id=?', [$id]);
    if (!$doc) { flash('error', 'Doctor not found.'); redirect('admin/doctors.php'); }
    $f = array_intersect_key($doc, $f);
}
$depts = array_column(fetch_all('SELECT DISTINCT department FROM doctors UNION SELECT DISTINCT department FROM health_services ORDER BY department'), 'department');

if (is_post()) {
    csrf_check();
    $f = ['name' => post('name'), 'email' => strtolower(post('email')), 'contact' => post('contact'), 'specialization' => post('specialization'),
          'department' => post('department'), 'status' => post('status') === 'inactive' ? 'inactive' : 'active'];
    $pass = (string)($_POST['password'] ?? '');
    $err = null;
    if (mb_strlen($f['name']) < 3) $err = 'Enter the doctor\'s full name.';
    elseif ($f['specialization'] === '' || $f['department'] === '') $err = 'Specialization and department are required.';
    elseif (!valid_phone($f['contact'])) $err = 'Enter a valid contact number.';
    elseif (!$doc && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid login email.';
    elseif (!$doc && fetch_val('SELECT COUNT(*) FROM users WHERE email=?', [$f['email']])) $err = 'That email is already registered.';
    elseif ((!$doc || $pass !== '') && ($e2 = strong_password($pass))) $err = $e2;

    if ($err) flash('error', $err);
    elseif ($doc) {
        q('UPDATE doctors SET name=?,specialization=?,department=?,contact=?,status=? WHERE doctor_id=?', [$f['name'], $f['specialization'], $f['department'], $f['contact'], $f['status'], $id]);
        if ($doc['user_id']) {
            q('UPDATE users SET name=?, phone=?, status=? WHERE user_id=?', [$f['name'], $f['contact'], $f['status'], $doc['user_id']]);
            if ($pass !== '') q('UPDATE users SET password=? WHERE user_id=?', [password_hash($pass, PASSWORD_DEFAULT), $doc['user_id']]);
        }
        log_activity((int)$u['user_id'], "Updated doctor #$id"); flash('success', 'Doctor updated.'); redirect('admin/doctors.php');
    } else {
        $pdo = db(); $pdo->beginTransaction();
        try {
            q("INSERT INTO users (name,email,password,role,phone,status) VALUES (?,?,?,'doctor',?,?)", [$f['name'], $f['email'], password_hash($pass, PASSWORD_DEFAULT), $f['contact'], $f['status']]);
            $uid = (int)$pdo->lastInsertId();
            q('INSERT INTO doctors (user_id,name,specialization,department,contact,status) VALUES (?,?,?,?,?,?)', [$uid, $f['name'], $f['specialization'], $f['department'], $f['contact'], $f['status']]);
            $pdo->commit();
            log_activity((int)$u['user_id'], 'Added doctor ' . $f['name']); flash('success', 'Doctor added. They can now log in with the email and password you set.'); redirect('admin/doctors.php');
        } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
    }
}
layout_top($id ? 'Edit Doctor' : 'Add Doctor', true);
?>
<div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-body">
  <form method="post" class="row g-3"><?= csrf_field() ?>
    <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" class="form-control" required value="<?= e($f['name']) ?>" placeholder="Dr. ..."></div>
    <div class="col-md-6"><label class="form-label">Contact number *</label><input name="contact" class="form-control" required value="<?= e($f['contact']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Specialization *</label><input name="specialization" class="form-control" required value="<?= e($f['specialization']) ?>" placeholder="e.g. Pediatrician"></div>
    <div class="col-md-6"><label class="form-label">Department *</label><input name="department" list="deptList" class="form-control" required value="<?= e($f['department']) ?>">
      <datalist id="deptList"><?php foreach ($depts as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-md-6"><label class="form-label">Login email <?= $doc ? '(cannot be changed)' : '*' ?></label>
      <input type="email" name="email" class="form-control" <?= $doc ? 'disabled' : 'required' ?> value="<?= e($doc['email'] ?? $f['email']) ?>"></div>
    <div class="col-md-6"><label class="form-label"><?= $doc ? 'New password (leave blank to keep)' : 'Password *' ?></label>
      <input type="password" name="password" class="form-control" <?= $doc ? '' : 'required' ?> minlength="8" autocomplete="new-password"></div>
    <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select">
      <option value="active" <?= $f['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $f['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
    <div class="col-12"><button class="btn btn-teal"><i class="bi bi-save me-1"></i>Save</button> <a href="<?= url('admin/doctors.php') ?>" class="btn btn-outline-secondary">Cancel</a></div>
  </form></div></div></div></div>
<?php layout_bottom(true); ?>
