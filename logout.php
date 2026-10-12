<?php
require_once __DIR__ . '/includes/auth.php';
// Logout only via POST + CSRF token (prevents forced logout through links/images)
if (is_post()) {
    csrf_check();
    logout_user();
    session_start();
    flash('success', 'You have been logged out.');
}
redirect('login.php');
