<?php
require_once __DIR__ . '/includes/layout.php';
if ($u = current_user()) redirect(home_for($u['role']));

$f = ['name' => '', 'email' => '', 'phone' => '', 'gender' => '', 'dob' => '', 'address' => ''];
$errors = [];
if (is_post()) {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = post($k);
    $f['email'] = strtolower($f['email']);
    $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');

    if (mb_strlen($f['name']) < 3 || !preg_match("/^[\p{L} .'-]+$/u", $f['name'])) $errors[] = 'Enter your full name (letters only, at least 3 characters).';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    elseif (fetch_val('SELECT COUNT(*) FROM users WHERE email=?', [$f['email']])) $errors[] = 'This email is already registered.';
    if (!valid_phone($f['phone'])) $errors[] = 'Enter a valid phone number.';
    if (!in_array($f['gender'], ['male', 'female', 'other'], true)) $errors[] = 'Select your gender.';
    if (!valid_date($f['dob']) || $f['dob'] > date('Y-m-d') || $f['dob'] < '1900-01-01') $errors[] = 'Enter a valid date of birth.';
    if ($f['address'] === '') $errors[] = 'Enter your address.';
    if ($err = strong_password($pw)) $errors[] = $err;
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        q('INSERT INTO users (name,email,password,role,phone,gender,dob,address) VALUES (?,?,?,?,?,?,?,?)',
          [$f['name'], $f['email'], password_hash($pw, PASSWORD_DEFAULT), 'citizen', $f['phone'], $f['gender'], $f['dob'], $f['address']]);
        $id = (int)db()->lastInsertId();
        notify($id, 'Welcome to E-Health Portal', 'Your account has been created. You can now book appointments with our doctors.');
        log_activity($id, 'Registered new citizen account');
        flash('success', 'Registration successful! Please log in.');
        redirect('login.php');
    }
}
layout_top('Register');
?>
<div class="container">
  <div class="card shadow-sm mx-auto my-4" style="max-width:760px"><div class="card-body p-4">
    <div class="text-center mb-4">
      <span class="icon-circle mb-2"><i class="bi bi-person-plus"></i></span>
      <h2 class="h4 mb-0">Create citizen account</h2>
    </div>
    <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>
    <form method="post" class="row g-3" novalidate>
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Full name</label><input name="name" class="form-control" value="<?= e($f['name']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($f['email']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($f['phone']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Gender</label>
        <select name="gender" class="form-select" required><option value="">Select</option>
          <?php foreach (['male', 'female', 'other'] as $g): ?><option value="<?= $g ?>" <?= $f['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">Date of birth</label><input type="date" name="dob" max="<?= date('Y-m-d') ?>" class="form-control" value="<?= e($f['dob']) ?>" required></div>
      <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($f['address']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="8"><div class="form-text">Minimum 8 characters, with letters and numbers.</div></div>
      <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password2" class="form-control" required></div>
      <div class="col-12"><button class="btn btn-teal w-100 py-2">Create account</button></div>
    </form>
    <p class="text-center small mt-3 mb-0">Already registered? <a href="<?= url('login.php') ?>">Login</a></p>
  </div></div>
</div>
<?php layout_bottom(); ?>
