<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();
header('Content-Type: application/json');

$customer = current_customer();
mark_customer_notifications_read($customer['id']);

echo json_encode(['ok' => true]);
