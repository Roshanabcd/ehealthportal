<?php
require_once __DIR__ . '/includes/layout.php';
layout_top('Contact');
?>
<div class="container mt-4">
  <h1 class="section-title h2 mb-4">Contact &amp; Help</h1>
  <div class="row g-3">
    <?php foreach ([['geo-alt-fill', 'Office', 'District Health Office, Kathmandu, Nepal'], ['telephone-fill', 'Phone', '01-4000000 (Sun - Fri, 9 AM - 5 PM)'], ['envelope-fill', 'Email', 'support@ehealth.gov.np'], ['exclamation-triangle-fill', 'Emergency', 'Ambulance 102 &bull; Health helpline 1115']] as [$i, $t, $v]): ?>
      <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body text-center">
        <span class="icon-circle mb-2"><i class="bi bi-<?= $i ?>"></i></span><h6><?= $t ?></h6><p class="small text-muted mb-0"><?= $v ?></p>
      </div></div></div>
    <?php endforeach; ?>
  </div>
  <div class="alert alert-warning mt-4"><i class="bi bi-exclamation-triangle me-1"></i> For a medical emergency, do not use this portal &mdash; call the ambulance or go to the nearest hospital immediately.</div>
</div>
<?php layout_bottom(); ?>
