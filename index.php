<?php
require_once __DIR__ . '/includes/layout.php';
$u = current_user();

$stats = [
    ['Health services', (int)fetch_val("SELECT COUNT(*) FROM health_services WHERE status='active'"), 'heart-pulse'],
    ['Registered doctors', (int)fetch_val("SELECT COUNT(*) FROM doctors WHERE status='active'"), 'person-badge'],
    ['Registered citizens', (int)fetch_val("SELECT COUNT(*) FROM users WHERE role='citizen'"), 'people'],
    ['Appointments handled', (int)fetch_val('SELECT COUNT(*) FROM appointments'), 'calendar-check'],
];
$services = fetch_all("SELECT * FROM health_services WHERE status='active' ORDER BY service_id LIMIT 6");
$icons = ['bi-stethoscope', 'bi-gender-female', 'bi-shield-plus', 'bi-emoji-smile', 'bi-eye', 'bi-lungs', 'bi-chat-heart', 'bi-bandaid'];

layout_top('Home');
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span class="badge bg-light text-teal mb-3 px-3 py-2"><i class="bi bi-patch-check-fill me-1"></i> Digital Public Health Services</span>
        <h1 class="display-5 mb-3">Government health services,<br>now just a click away.</h1>
        <p class="lead mb-4">Find public health services, book appointments with doctors, track your requests and view your health records securely &mdash; without standing in long queues or carrying paper files.</p>
        <div class="d-flex flex-wrap gap-2">
          <?php if ($u): ?>
            <a href="<?= url(home_for($u['role'])) ?>" class="btn btn-light btn-lg text-teal fw-semibold">Go to my dashboard</a>
          <?php else: ?>
            <a href="<?= url('register.php') ?>" class="btn btn-light btn-lg text-teal fw-semibold"><i class="bi bi-person-plus me-1"></i> Register as citizen</a>
            <a href="<?= url('login.php') ?>" class="btn btn-outline-light btn-lg">Login</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <div class="hero-card">
          <h6 class="mb-3"><i class="bi bi-lightning-charge-fill me-1"></i> What you can do</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Create a free citizen account</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Browse government health services</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Book a doctor by choosing a free time slot</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Track appointment status &amp; get notifications</li>
            <li><i class="bi bi-check-circle-fill me-2"></i>View &amp; print your health records</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container stat-strip">
  <div class="row g-3">
    <?php foreach ($stats as [$label, $n, $icon]): ?>
      <div class="col-6 col-lg-3"><div class="stat-box"><i class="bi bi-<?= $icon ?> text-teal fs-4"></i><div class="num"><?= $n ?></div><div class="text-muted small"><?= e($label) ?></div></div></div>
    <?php endforeach; ?>
  </div>
</div>

<section class="container mt-5 pt-3">
  <div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="section-title h3">Available health services</h2>
    <a href="<?= url('services.php') ?>" class="btn btn-outline-teal btn-sm">View all</a>
  </div>
  <div class="row g-3">
    <?php foreach ($services as $i => $s): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card svc-card"><div class="card-body">
          <span class="icon-circle mb-3"><i class="bi <?= $icons[$i % count($icons)] ?>"></i></span>
          <h5 class="mb-1"><?= e($s['service_name']) ?></h5>
          <div class="small text-teal mb-2"><?= e($s['department']) ?></div>
          <p class="text-muted small mb-0"><?= e(mb_strimwidth($s['description'], 0, 110, '...')) ?></p>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container mt-5 pt-3">
  <h2 class="section-title h3 text-center mb-4">How it works</h2>
  <div class="row g-4 text-center">
    <?php foreach ([['Register', 'Create your citizen account with basic details.'], ['Choose a service', 'Pick a health service and an available doctor.'], ['Book a slot', 'Select a date and time that suits you.'], ['Visit &amp; track', 'Get approval notifications and see your records online.']] as $i => [$t, $d]): ?>
      <div class="col-6 col-lg-3">
        <span class="step-num mb-2"><?= $i + 1 ?></span>
        <h6><?= $t ?></h6><p class="small text-muted"><?= $d ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php layout_bottom(); ?>
