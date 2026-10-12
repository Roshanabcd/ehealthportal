<?php
require_once __DIR__ . '/auth.php';

/** Sidebar menu per role: [label, file, icon] */
function menu_for(string $role): array
{
    switch ($role) {
        case 'admin': return [
            ['Dashboard', 'admin/dashboard.php', 'speedometer2'],
            ['Appointments', 'admin/appointments.php', 'calendar-check'],
            ['Health Services', 'admin/services.php', 'heart-pulse'],
            ['Doctors', 'admin/doctors.php', 'person-badge'],
            ['Users', 'admin/users.php', 'people'],
            ['Health Records', 'admin/records.php', 'file-medical'],
            ['Reports', 'admin/reports.php', 'bar-chart-line'],
            ['Emergencies', 'admin/emergencies.php', 'exclamation-triangle-fill'],
            ['Activity Log', 'admin/logs.php', 'clock-history'],
        ];
        case 'doctor': return [
            ['Dashboard', 'doctor/dashboard.php', 'speedometer2'],
            ['My Appointments', 'doctor/appointments.php', 'calendar-check'],
            ['Health Records', 'doctor/records.php', 'file-medical'],
            ['My Patients', 'doctor/patients.php', 'people'],
            ['Notifications', 'notifications.php', 'bell'],
        ];
        default: return [
            ['Dashboard', 'citizen/dashboard.php', 'speedometer2'],
            ['Book Appointment', 'citizen/book.php', 'calendar-plus'],
            ['My Appointments', 'citizen/appointments.php', 'calendar-check'],
            ['My Health Records', 'citizen/records.php', 'file-medical'],
            ['Notifications', 'notifications.php', 'bell'],
        ];
    }
}

function layout_top(string $title, bool $panel = false): void
{
    $u = current_user();
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $unread = $u ? unread_count((int)$u['user_id']) : 0;
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(APP_SHORT) ?></title>
<link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230e7c7b'/%3E%3Cpath d='M40 20h20v20h20v20H60v20H40V60H20V40h20z' fill='white'/%3E%3C/svg%3E">
</head>
<body class="<?= $panel ? 'panel-body' : '' ?>">

<nav class="navbar navbar-expand-lg navbar-dark topbar sticky-top">
  <div class="container-fluid px-lg-4">
    <?php if ($panel): ?>
      <button class="btn btn-link text-white d-lg-none me-2 p-0" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list fs-2"></i></button>
    <?php endif; ?>
    <a class="navbar-brand d-flex align-items-center" href="<?= url($u ? home_for($u['role']) : 'index.php') ?>">
      <span class="brand-mark me-2"><i class="bi bi-plus-lg"></i></span>
      <span class="lh-1"><strong>E-Health Portal</strong><small class="d-none d-sm-block">Government Service Portal</small></span>
    </a>
    <?php if (!$panel): ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
    <?php endif; ?>
    <div class="<?= $panel ? 'ms-auto' : 'collapse navbar-collapse' ?>" id="mainNav">
      <?php if (!$panel): ?>
      <ul class="navbar-nav ms-auto me-lg-3">
        <li class="nav-item"><a class="nav-link <?= basename($script) === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($script) === 'services.php' ? 'active' : '' ?>" href="<?= url('services.php') ?>">Services</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($script) === 'doctors.php' && !str_contains($script, 'admin') ? 'active' : '' ?>" href="<?= url('doctors.php') ?>">Doctors</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($script) === 'about.php' ? 'active' : '' ?>" href="<?= url('about.php') ?>">About</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($script) === 'contact.php' ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Contact</a></li>
        <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-danger mt-1 fw-bold" href="<?= url('emergency.php') ?>"><i class="bi bi-exclamation-triangle-fill"></i> SOS</a></li>
      </ul>
      <?php endif; ?>
      <div class="d-flex align-items-center gap-2">
        <?php if ($u): ?>
          <?php if (true): ?>
            <a href="<?= url('notifications.php') ?>" class="btn btn-sm btn-outline-light position-relative" title="Notifications">
              <i class="bi bi-bell"></i>
              <?php if ($unread): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread ?></span><?php endif; ?>
            </a>
          <?php endif; ?>
          <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle me-1"></i><?= e(explode(' ', $u['name'])[0]) ?></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><span class="dropdown-item-text small text-muted"><?= e($u['email']) ?><br><span class="badge bg-info text-dark"><?= e(ucfirst($u['role'])) ?></span></span></li>
              <li><hr class="dropdown-divider"></li>
              <?php if (!$panel): ?><li><a class="dropdown-item" href="<?= url(home_for($u['role'])) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li><?php endif; ?>
              <li><a class="dropdown-item" href="<?= url('profile.php') ?>"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
              <li>
                <form method="post" action="<?= url('logout.php') ?>"><?= csrf_field() ?>
                  <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= url('login.php') ?>" class="btn btn-sm btn-outline-light">Login</a>
          <a href="<?= url('register.php') ?>" class="btn btn-sm btn-light text-teal fw-semibold">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<?php if ($panel && $u): ?>
<div class="panel-wrap">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-role"><i class="bi bi-shield-check me-1"></i><?= e(ucfirst($u['role'])) ?> Panel</div>
    <ul class="nav flex-column">
      <?php foreach (menu_for($u['role']) as [$label, $file, $icon]):
          $active = str_ends_with($script, '/' . $file) || (basename(dirname($script)) . '/' . basename($script)) === $file; ?>
        <li class="nav-item">
          <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= url($file) ?>"><i class="bi bi-<?= $icon ?> me-2"></i><?= e($label) ?></a>
        </li>
      <?php endforeach; ?>
      <li class="nav-item mt-2"><a class="nav-link <?= basename($script) === 'profile.php' ? 'active' : '' ?>" href="<?= url('profile.php') ?>"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
    </ul>
  </aside>
  <main class="panel-main">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <h1 class="h3 page-title mb-0"><?= e($title) ?></h1>
      <span class="text-muted small d-none d-md-block"><i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?></span>
    </div>
    <?= flash_render() ?>
<?php else: ?>
<main>
<div class="container mt-3"><?= flash_render() ?></div>
<?php endif;
}

function layout_bottom(bool $panel = false): void
{
    if ($panel): ?>
  </main>
</div>
<?php else: ?>
</main>
<footer class="site-footer mt-5">
  <div class="container py-4">
    <div class="row g-4">
      <div class="col-md-5">
        <h6 class="text-white"><i class="bi bi-plus-square-fill me-2"></i><?= e(APP_NAME) ?></h6>
        <p class="small mb-0">A centralized digital platform for selected public health services &mdash; online appointments, service information and secure health records. Developed as an academic e-governance prototype.</p>
      </div>
      <div class="col-6 col-md-3">
        <h6 class="text-white">Quick Links</h6>
        <ul class="list-unstyled small mb-0">
          <li><a href="<?= url('services.php') ?>">Health Services</a></li>
          <li><a href="<?= url('doctors.php') ?>">Our Doctors</a></li>
          <li><a href="<?= url('register.php') ?>">Create Account</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4">
        <h6 class="text-white">Emergency</h6>
        <p class="small mb-0"><i class="bi bi-telephone-fill me-1"></i> Ambulance: 102<br><i class="bi bi-telephone-fill me-1"></i> Health helpline: 1115</p>
      </div>
    </div>
    <hr class="border-secondary">
    <div class="small text-center">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &mdash; B.Sc. CSIT Project, National Infotech College, Tribhuvan University.</div>
  </div>
</footer>
<?php endif; ?>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
<?php
}
