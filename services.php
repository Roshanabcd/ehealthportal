<?php
require_once __DIR__ . '/includes/layout.php';
$u = current_user();
$search = get('q'); $dept = get('department');

$where = "status='active'"; $params = [];
if ($search !== '') { $where .= ' AND (service_name LIKE ? OR description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($dept !== '') { $where .= ' AND department=?'; $params[] = $dept; }
$services = fetch_all("SELECT * FROM health_services WHERE $where ORDER BY service_name", $params);
$departments = array_column(fetch_all("SELECT DISTINCT department FROM health_services WHERE status='active' ORDER BY department"), 'department');
$icons = ['bi-stethoscope', 'bi-gender-female', 'bi-shield-plus', 'bi-emoji-smile', 'bi-eye', 'bi-lungs', 'bi-chat-heart', 'bi-bandaid'];

layout_top('Health Services');
?>
<div class="container mt-4">
  <h1 class="section-title h2 mb-4">Government Health Services</h1>
  <form class="row g-2 mb-4" method="get">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search services..."></div>
    <div class="col-md-4">
      <select class="form-select" name="department"><option value="">All departments</option>
        <?php foreach ($departments as $d): ?><option <?= $d === $dept ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-grid d-md-flex gap-2"><button class="btn btn-teal"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="services.php">Reset</a></div>
  </form>

  <?php if (!$services): ?>
    <div class="alert alert-info">No services matched your search.</div>
  <?php endif; ?>
  <div class="row g-3">
    <?php foreach ($services as $i => $s): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card svc-card"><div class="card-body d-flex flex-column">
          <span class="icon-circle mb-3"><i class="bi <?= $icons[$i % count($icons)] ?>"></i></span>
          <h5 class="mb-1"><?= e($s['service_name']) ?></h5>
          <div class="small text-teal mb-2"><i class="bi bi-building me-1"></i><?= e($s['department']) ?></div>
          <p class="text-muted small flex-grow-1"><?= e($s['description']) ?></p>
          <?php if ($u && $u['role'] === 'citizen'): ?>
            <a class="btn btn-teal btn-sm" href="<?= url('citizen/book.php?service_id=' . $s['service_id']) ?>"><i class="bi bi-calendar-plus me-1"></i>Book appointment</a>
          <?php elseif (!$u): ?>
            <a class="btn btn-outline-teal btn-sm" href="<?= url('login.php') ?>">Login to book</a>
          <?php endif; ?>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php layout_bottom(); ?>
