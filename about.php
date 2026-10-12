<?php
require_once __DIR__ . '/includes/layout.php';
layout_top('About');
?>
<div class="container mt-4">
  <h1 class="section-title h2 mb-4">About the Portal</h1>
  <div class="row g-4">
    <div class="col-lg-7">
      <p>The <strong>E-Health Government Service Portal</strong> is a centralized web platform that digitizes selected public health services. Citizens no longer need to visit offices repeatedly or depend on paper registers to book appointments, follow up on requests or access their records.</p>
      <h5 class="mt-4">Problems it addresses</h5>
      <ul>
        <li>Dependence on physical visits and paper-based procedures</li>
        <li>Long waiting periods and difficulty tracking appointment status</li>
        <li>Duplicated, misplaced or inconsistent manual records</li>
        <li>No single platform linking citizens, doctors and administrators</li>
      </ul>
      <h5 class="mt-4">Main modules</h5>
      <div class="row g-2">
        <?php foreach (['Citizen registration &amp; login', 'Health service information', 'Online appointment booking', 'Doctor / service provider management', 'Health records with authorized access', 'Status tracking &amp; notifications', 'Administrator dashboard &amp; user management'] as $m): ?>
          <div class="col-md-6"><i class="bi bi-check-circle-fill text-teal me-2"></i><?= $m ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card"><div class="card-header">User roles</div>
        <ul class="list-group list-group-flush">
          <li class="list-group-item"><strong>Citizen</strong> &mdash; registers, books appointments, views own records.</li>
          <li class="list-group-item"><strong>Doctor</strong> &mdash; sees assigned appointments, creates health records.</li>
          <li class="list-group-item"><strong>Administrator</strong> &mdash; manages users, services, doctors, appointments and reports.</li>
        </ul>
      </div>
      <div class="alert alert-secondary mt-3 small mb-0"><i class="bi bi-info-circle me-1"></i>This is an academic prototype demonstrating e-governance in public health service administration. It does not replace a complete clinical system.</div>
    </div>
  </div>
</div>
<?php layout_bottom(); ?>
