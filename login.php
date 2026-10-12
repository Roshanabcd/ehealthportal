<?php
require_once __DIR__ . '/includes/layout.php';
if ($u = current_user()) redirect(home_for($u['role']));

$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    $pass = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
        flash('error', 'Please enter a valid email and password.');
    } else {
        [$ok, $res] = attempt_login($email, $pass);
        if ($ok) { flash('success', 'Welcome back!'); redirect(home_for($res)); }
        flash('error', $res);
    }
}
layout_top('Login');
?>
<div class="container">
  <div class="auth-card card shadow-sm"><div class="card-body p-4">
    <div class="text-center mb-4">
      <span class="icon-circle mb-2"><i class="bi bi-box-arrow-in-right"></i></span>
      <h2 class="h4 mb-0">Login to your account</h2>
      <p class="text-muted small">Citizens, doctors and administrators</p>
    </div>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus></div>
      <div class="mb-3"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-teal w-100 py-2">Login</button>
    </form>
    <p class="text-center small mt-3 mb-0">New citizen? <a href="<?= url('register.php') ?>">Create an account</a></p>
  </div></div>

</div>
<?php layout_bottom(); ?>
