<?php
require_once __DIR__ . '/includes/layout.php';
$u = require_role('admin', 'doctor', 'citizen');

if (is_post()) {
    csrf_check();
    if (post('action') === 'clear') {
        q('DELETE FROM notifications WHERE user_id=?', [$u['user_id']]);
        flash('success', 'All notifications cleared.');
    }
    redirect('notifications.php');
}

$list = fetch_all('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC, notification_id DESC LIMIT 50', [$u['user_id']]);
// mark as read *after* loading, so unread items are still highlighted on this view
q('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0', [$u['user_id']]);

layout_top('Notifications', true);
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-bell me-1"></i>Your notifications</span>
    <?php if ($list): ?>
    <form method="post" data-confirm="Delete all notifications?"><?= csrf_field() ?><input type="hidden" name="action" value="clear">
      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Clear all</button></form>
    <?php endif; ?>
  </div>
  <div class="list-group list-group-flush">
    <?php foreach ($list as $n): ?>
      <div class="list-group-item <?= $n['is_read'] ? '' : 'notif-unread' ?>">
        <div class="d-flex justify-content-between"><strong><?= e($n['title']) ?></strong><small class="text-muted"><?= fmt_datetime($n['created_at']) ?></small></div>
        <div class="small"><?= e($n['message']) ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$list): ?><div class="list-group-item text-center text-muted py-5"><i class="bi bi-bell-slash fs-3 d-block"></i>You have no notifications.</div><?php endif; ?>
  </div>
</div>
<?php layout_bottom(true); ?>
