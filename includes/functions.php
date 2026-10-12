<?php
require_once __DIR__ . '/db.php';

/* ---------- Session (hardened) ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_name('EHEALTHSESS');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/* ---------- Output / URL helpers ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$f['type']] ?? 'info';
        $icon = ['success' => 'check-circle-fill', 'danger' => 'exclamation-triangle-fill', 'warning' => 'exclamation-circle-fill', 'info' => 'info-circle-fill'][$cls];
        $out .= '<div class="alert alert-' . $cls . ' d-flex align-items-center alert-dismissible fade show" role="alert">'
              . '<i class="bi bi-' . $icon . ' me-2"></i><div>' . e($f['msg']) . '</div>'
              . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
/** Call at the top of every POST handler. */
function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
        if (!$ok) { http_response_code(419); die('Security token expired. Please go back, refresh the page and try again.'); }
    }
}
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function post(string $k, string $default = ''): string { return trim((string)($_POST[$k] ?? $default)); }
function get(string $k, string $default = ''): string { return trim((string)($_GET[$k] ?? $default)); }

/* ---------- Validation helpers ---------- */
function valid_phone(string $p): bool { return (bool)preg_match('/^[0-9+\-\s]{7,15}$/', $p); }
function valid_date(string $d): bool { $x = DateTime::createFromFormat('Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; }
function strong_password(string $p): ?string
{
    if (strlen($p) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/[0-9]/', $p)) return 'Password must contain letters and numbers.';
    return null;
}

/* ---------- Notifications & activity log ---------- */
function notify(int $userId, string $title, string $message): void
{
    q('INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)', [$userId, $title, $message]);
}
function log_activity(?int $userId, string $action): void
{
    q('INSERT INTO activity_logs (user_id,action,ip_address) VALUES (?,?,?)', [$userId, mb_substr($action, 0, 255), $_SERVER['REMOTE_ADDR'] ?? null]);
}
function unread_count(int $userId): int
{
    return (int)fetch_val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$userId]);
}

/* ---------- Display helpers ---------- */
function status_badge(string $s): string
{
    $map = ['pending' => 'warning text-dark', 'approved' => 'primary', 'completed' => 'success',
            'cancelled' => 'secondary', 'rejected' => 'danger', 'active' => 'success', 'inactive' => 'secondary'];
    return '<span class="badge bg-' . ($map[$s] ?? 'secondary') . '">' . e(ucfirst($s)) . '</span>';
}
function fmt_date(?string $d): string { return $d ? date('d M Y', strtotime($d)) : '-'; }
function fmt_time(?string $t): string { return $t ? date('h:i A', strtotime($t)) : '-'; }
function fmt_datetime(?string $d): string { return $d ? date('d M Y, h:i A', strtotime($d)) : '-'; }
function age_from(?string $dob): string
{
    if (!$dob) return '-';
    return (string)(new DateTime($dob))->diff(new DateTime('today'))->y;
}

/* ---------- Appointment slot logic ---------- */
function all_slots(): array
{
    $slots = [];
    for ($t = strtotime(SLOT_START); $t <= strtotime(SLOT_END); $t += SLOT_MINUTES * 60) $slots[] = date('H:i', $t);
    return $slots;
}
/** Free slots for a doctor on a date (cancelled / rejected bookings free the slot). */
function free_slots(int $doctorId, string $date, ?int $ignoreAppointment = null): array
{
    if (!valid_date($date)) return [];
    if ((int)date('w', strtotime($date)) === OFF_DAY) return [];
    $taken = array_column(fetch_all(
        "SELECT TIME_FORMAT(appointment_time,'%H:%i') t FROM appointments
         WHERE doctor_id=? AND appointment_date=? AND status IN ('pending','approved','completed') AND appointment_id<>?",
        [$doctorId, $date, $ignoreAppointment ?? 0]), 't');
    $free = array_values(array_diff(all_slots(), $taken));
    if ($date === date('Y-m-d')) { $now = date('H:i'); $free = array_values(array_filter($free, fn($s) => $s > $now)); }
    return $free;
}

/** Simple pagination: returns [offset, perPage, currentPage]. */
function paginate(int $total, int $perPage = 10): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    return [($page - 1) * $perPage, $perPage, $page, $pages];
}
function pagination_html(int $page, int $pages): string
{
    if ($pages <= 1) return '';
    $qs = $_GET; $out = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs['page'] = $i;
        $out .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="?' . e(http_build_query($qs)) . '">' . $i . '</a></li>';
    }
    return $out . '</ul></nav>';
}

/** Hidden delete/confirm form button helper */
function confirm_attr(string $msg): string { return 'onsubmit="return confirm(\'' . e(addslashes($msg)) . '\')"'; }

/* ---------- Doctors that can serve a health service (same department) ---------- */
function doctors_for_service(int $serviceId): array
{
    $svc = fetch_one("SELECT department FROM health_services WHERE service_id=? AND status='active'", [$serviceId]);
    if (!$svc) return [];
    // A service department may be a combined name such as "Gynecology & Pediatrics"
    $parts = array_values(array_filter(array_map('trim', preg_split('/\s*&\s*/', $svc['department']))));
    $parts[] = $svc['department'];
    $ph = implode(',', array_fill(0, count($parts), '?'));
    return fetch_all("SELECT doctor_id,name,specialization FROM doctors WHERE status='active' AND department IN ($ph) ORDER BY name", $parts);
}

/* ---------- Appointment status workflow ---------- */
/** pending -> approved/rejected/cancelled ; approved -> completed/cancelled */
function appointment_transitions(): array
{
    return ['pending' => ['approved', 'rejected', 'cancelled'], 'approved' => ['completed', 'cancelled']];
}
/** Change status, notify the citizen, write the activity log. Returns false if the change is not allowed. */
function set_appointment_status(int $id, string $new, string $note, array $actor): bool
{
    $a = fetch_one("SELECT a.*, s.service_name, d.name doctor_name, d.user_id doctor_user
                    FROM appointments a
                    JOIN health_services s ON s.service_id=a.service_id
                    JOIN doctors d ON d.doctor_id=a.doctor_id WHERE a.appointment_id=?", [$id]);
    if (!$a || !in_array($new, appointment_transitions()[$a['status']] ?? [], true)) return false;
    $note = mb_substr(trim($note), 0, 300);
    q('UPDATE appointments SET status=?, admin_note=? WHERE appointment_id=?', [$new, $note !== '' ? $note : $a['admin_note'], $id]);
    $msg = "Your appointment for {$a['service_name']} with {$a['doctor_name']} on " . fmt_date($a['appointment_date'])
         . ' at ' . fmt_time($a['appointment_time']) . " has been $new." . ($note !== '' ? " Remarks: $note" : '');
    notify((int)$a['user_id'], 'Appointment ' . $new, $msg);
    if ($new === 'cancelled' && $a['doctor_user'] && (int)$a['doctor_user'] !== (int)$actor['user_id'])
        notify((int)$a['doctor_user'], 'Appointment cancelled', "Appointment #$id on " . fmt_date($a['appointment_date']) . ' was cancelled.');
    log_activity((int)$actor['user_id'], "Appointment #$id marked $new");
    return true;
}

/* ---------- Dashboard KPI card ---------- */
function kpi(string $icon, $value, string $label): string
{
    return '<div class="card kpi h-100"><span class="icon-circle"><i class="bi bi-' . e($icon) . '"></i></span>'
         . '<div><div class="val">' . e($value) . '</div><div class="lbl">' . e($label) . '</div></div></div>';
}

/** Empty-state row for tables */
function empty_row(int $cols, string $msg = 'No records found.'): string
{
    return '<tr><td colspan="' . $cols . '" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i>' . e($msg) . '</td></tr>';
}
