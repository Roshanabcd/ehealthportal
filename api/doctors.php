<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) { http_response_code(401); echo '[]'; exit; }
echo json_encode(doctors_for_service((int)get('service_id')), JSON_UNESCAPED_UNICODE);
