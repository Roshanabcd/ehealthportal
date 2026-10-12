<?php
/**
 * E-Health Government Service Portal - configuration
 * Default XAMPP MySQL: user "root" with an EMPTY password.
 * Change these values only if you set a MySQL password.
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'ehealth_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'E-Health Government Service Portal');
define('APP_SHORT', 'E-Health Portal');

// Auto-detect the URL path of this project (works for any folder name in htdocs).
$__root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__proj = str_replace('\\', '/', realpath(__DIR__ . '/..'));
define('BASE_URL', rtrim(str_replace($__root, '', $__proj), '/'));
define('ROOT_PATH', dirname(__DIR__));

// Appointment settings
define('SLOT_START', '09:00');   // first slot of the day
define('SLOT_END',   '16:30');   // last slot of the day
define('SLOT_MINUTES', 30);
define('MAX_BOOKING_DAYS', 60);  // how far ahead a citizen can book
define('OFF_DAY', 6);            // 6 = Saturday (weekly holiday in Nepal), no appointments

date_default_timezone_set('Asia/Kathmandu');
