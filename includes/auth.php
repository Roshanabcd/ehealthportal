<?php
require_once __DIR__ . '/functions.php';

const MAX_FAILED_LOGINS = 5;
const LOCK_MINUTES = 10;

function current_user(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = isset($_SESSION['user_id'])
            ? fetch_one('SELECT user_id,name,email,role,phone,gender,dob,address,status FROM users WHERE user_id=?', [$_SESSION['user_id']])
            : null;
        if ($u && $u['status'] !== 'active') { $u = null; unset($_SESSION['user_id']); }
    }
    return $u;
}
function is_logged_in(): bool { return current_user() !== null; }

function home_for(string $role): string
{
    return ['admin' => 'admin/dashboard.php', 'doctor' => 'doctor/dashboard.php', 'citizen' => 'citizen/dashboard.php'][$role] ?? 'index.php';
}

/** Guard: only allow listed roles on a page. */
function require_role(string ...$roles): array
{
    $u = current_user();
    if (!$u) { flash('warning', 'Please log in to continue.'); redirect('login.php'); }
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); flash('error', 'You are not authorized to view that page.'); redirect(home_for($u['role'])); }
    return $u;
}

/** Returns [ok(bool), message]. Includes brute-force lockout. */
function attempt_login(string $email, string $password): array
{
    $u = fetch_one('SELECT * FROM users WHERE email=?', [$email]);
    if (!$u) return [false, 'Invalid email or password.'];
    if ($u['locked_until'] && strtotime($u['locked_until']) > time())
        return [false, 'Account temporarily locked after too many failed attempts. Try again in a few minutes.'];
    if (!password_verify($password, $u['password'])) {
        $n = $u['failed_logins'] + 1;
        if ($n >= MAX_FAILED_LOGINS) {
            q('UPDATE users SET failed_logins=0, locked_until=DATE_ADD(NOW(), INTERVAL ' . LOCK_MINUTES . ' MINUTE) WHERE user_id=?', [$u['user_id']]);
        } else {
            q('UPDATE users SET failed_logins=? WHERE user_id=?', [$n, $u['user_id']]);
        }
        log_activity((int)$u['user_id'], 'Failed login attempt');
        return [false, 'Invalid email or password.'];
    }
    if ($u['status'] !== 'active') return [false, 'Your account has been deactivated. Please contact the administrator.'];

    q('UPDATE users SET failed_logins=0, locked_until=NULL WHERE user_id=?', [$u['user_id']]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['user_id'];
    log_activity((int)$u['user_id'], 'Logged in');
    if (password_needs_rehash($u['password'], PASSWORD_DEFAULT))
        q('UPDATE users SET password=? WHERE user_id=?', [password_hash($password, PASSWORD_DEFAULT), $u['user_id']]);
    return [true, $u['role']];
}

function logout_user(): void
{
    if (isset($_SESSION['user_id'])) log_activity((int)$_SESSION['user_id'], 'Logged out');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Doctor row for the logged-in doctor user. */
function my_doctor(int $userId): ?array
{
    return fetch_one('SELECT * FROM doctors WHERE user_id=?', [$userId]);
}

/** Who may open a health record? admin: all | citizen: own | doctor: own records or patients seen by that doctor. */
function can_view_record(array $user, array $record): bool
{
    if ($user['role'] === 'admin') return true;
    if ($user['role'] === 'citizen') return (int)$record['user_id'] === (int)$user['user_id'];
    if ($user['role'] === 'doctor') {
        $d = my_doctor((int)$user['user_id']);
        if (!$d) return false;
        return (int)$record['doctor_id'] === (int)$d['doctor_id']
            || (bool)fetch_val("SELECT 1 FROM appointments WHERE doctor_id=? AND user_id=? AND status IN ('approved','completed') LIMIT 1", [$d['doctor_id'], $record['user_id']]);
    }
    return false;
}
