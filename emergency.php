<?php
require_once __DIR__ . '/includes/layout.php';

$u = current_user(); // null if not logged in
$f = [
    'requester_name' => $u ? $u['name'] : '',
    'contact_number' => $u ? $u['phone'] : '',
    'emergency_type' => '',
    'location'       => $u ? $u['address'] : '',
    'description'    => ''
];
$errors = [];
$success = false;

if (is_post()) {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = post($k);

    if (mb_strlen($f['requester_name']) < 2) $errors[] = 'Enter a valid name.';
    if (!valid_phone($f['contact_number'])) $errors[] = 'Enter a valid phone number.';
    if ($f['emergency_type'] === '') $errors[] = 'Select the type of emergency.';
    if (mb_strlen($f['location']) < 3) $errors[] = 'Enter a detailed location.';
    if (mb_strlen($f['description']) < 5) $errors[] = 'Provide some details about the emergency.';

    if (!$errors) {
        q('INSERT INTO emergencies (user_id, requester_name, contact_number, emergency_type, location, description) VALUES (?,?,?,?,?,?)',
          [$u ? $u['user_id'] : null, $f['requester_name'], $f['contact_number'], $f['emergency_type'], $f['location'], $f['description']]);
        
        $success = true;
    }
}
layout_top('Emergency Request');
?>
<div class="container">
  <div class="card shadow-sm mx-auto my-4 border-danger" style="max-width:760px">
    <div class="card-header bg-danger text-white p-3">
        <h2 class="h4 mb-0 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> Request Emergency Assistance</h2>
    </div>
    <div class="card-body p-4">
    
    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <h4 class="alert-heading">Emergency Request Received</h4>
            <p>Your request for emergency assistance has been submitted successfully. Help will be dispatched shortly.</p>
            <p class="mb-0"><strong>Please stay calm and keep your phone nearby.</strong></p>
            <div class="mt-4">
                <a href="<?= url('index.php') ?>" class="btn btn-outline-danger">Return to Homepage</a>
            </div>
        </div>
    <?php else: ?>
        <p class="text-muted text-center mb-4">Please fill out this form to request an ambulance or report an urgent medical situation. This service is available 24/7.</p>
        
        <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>
        
        <form method="post" class="row g-3" novalidate>
          <?= csrf_field() ?>
          
          <div class="col-md-6">
              <label class="form-label text-danger fw-bold">Your Name *</label>
              <input name="requester_name" class="form-control" value="<?= e($f['requester_name']) ?>" required <?= $u ? 'readonly' : '' ?>>
          </div>
          
          <div class="col-md-6">
              <label class="form-label text-danger fw-bold">Contact Number *</label>
              <input name="contact_number" class="form-control" value="<?= e($f['contact_number']) ?>" required>
          </div>
          
          <div class="col-md-12">
              <label class="form-label text-danger fw-bold">Emergency Type *</label>
              <select name="emergency_type" class="form-select border-danger" required>
                  <option value="">-- Select Emergency Type --</option>
                  <option value="Ambulance Request" <?= $f['emergency_type'] === 'Ambulance Request' ? 'selected' : '' ?>>Ambulance Request</option>
                  <option value="Accident" <?= $f['emergency_type'] === 'Accident' ? 'selected' : '' ?>>Accident / Trauma</option>
                  <option value="Medical SOS" <?= $f['emergency_type'] === 'Medical SOS' ? 'selected' : '' ?>>Medical SOS</option>
                  <option value="Fire / Burn" <?= $f['emergency_type'] === 'Fire / Burn' ? 'selected' : '' ?>>Fire / Burn Injury</option>
                  <option value="Other" <?= $f['emergency_type'] === 'Other' ? 'selected' : '' ?>>Other Urgent Situation</option>
              </select>
          </div>
          
          <div class="col-12">
              <label class="form-label text-danger fw-bold">Exact Location / Address *</label>
              <input name="location" class="form-control border-danger" value="<?= e($f['location']) ?>" required placeholder="E.g., Baneshwor Chowk, Near ABC Bank">
          </div>
          
          <div class="col-12">
              <label class="form-label text-danger fw-bold">Description of Emergency *</label>
              <textarea name="description" class="form-control" rows="3" required placeholder="Describe what happened and who needs help..."><?= e($f['description']) ?></textarea>
          </div>
          
          <div class="col-12 mt-4">
              <button class="btn btn-danger btn-lg w-100 py-3 fw-bold shadow"><i class="bi bi-send-fill me-2"></i> SUBMIT EMERGENCY REQUEST</button>
          </div>
        </form>
    <?php endif; ?>
  </div></div>
</div>
<?php layout_bottom(); ?>
