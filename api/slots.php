<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) { http_response_code(401); echo json_encode(['slots' => [], 'message' => 'Please log in.']); exit; }

$doctorId = (int)get('doctor_id'); $date = get('date');
$ignore = (int)get('ignore') ?: null; // when rescheduling
$msg = '';
if (!valid_date($date) || $date < date('Y-m-d')) $msg = 'Choose a valid future date.';
elseif ((int)date('w', strtotime($date)) === OFF_DAY) $msg = 'No appointments on Saturdays (weekly holiday).';
elseif ($date > date('Y-m-d', strtotime('+' . MAX_BOOKING_DAYS . ' days'))) $msg = 'You can book up to ' . MAX_BOOKING_DAYS . ' days ahead.';
if ($msg) { echo json_encode(['slots' => [], 'message' => $msg]); exit; }

$out = array_map(fn($s) => ['value' => $s, 'label' => date('h:i A', strtotime($s))], free_slots($doctorId, $date, $ignore));
echo json_encode(['slots' => $out, 'message' => $out ? '' : 'All slots are booked on this date. Try another date.']);
