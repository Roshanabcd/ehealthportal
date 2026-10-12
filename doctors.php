<?php
require_once __DIR__ . '/includes/layout.php';
$search = get('q');
$where = "status='active'"; $params = [];
if ($search !== '') { $where .= ' AND (name LIKE ? OR specialization LIKE ? OR department LIKE ?)'; $params = ["%$search%", "%$search%", "%$search%"]; }
$doctors = fetch_all("SELECT * FROM doctors WHERE $where ORDER BY name", $params);
layout_top('Our Doctors');
?>
<div class="container mt-4">
  <h1 class="section-title h2 mb-4">Our Doctors &amp; Service Providers</h1>
  <form class="row g-2 mb-4" method="get">
    <div class="col-md-6"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search by name, specialization or department..."></div>
    <div class="col-md-3"><button class="btn btn-teal"><i class="bi bi-search me-1"></i>Search</button></div>
  </form>
  <?php if (!$doctors): ?><div class="alert alert-info">No doctors found.</div><?php endif; ?>
  <div class="row g-3">
    <?php foreach ($doctors as $d): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card svc-card"><div class="card-body d-flex gap-3">
          <span class="icon-circle flex-shrink-0"><i class="bi bi-person-badge"></i></span>
          <div>
            <h5 class="mb-0"><?= e($d['name']) ?></h5>
            <div class="text-teal small"><?= e($d['specialization']) ?></div>
            <div class="text-muted small"><i class="bi bi-building me-1"></i><?= e($d['department']) ?></div>
          </div>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php layout_bottom(); ?>
