<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();
header('Content-Type: application/json');

$customer = current_customer();
$items = get_notifications('customer', $customer['id'], 10);

$out = [];
foreach ($items as $n) {
    $out[] = [
        'id' => (int)$n['id'],
        'type' => $n['type'],
        'title' => $n['title'],
        'message' => $n['message'],
        'link' => $n['link'],
        'created_at' => date('M j, g:i A', strtotime($n['created_at'])),
        'icon' => notif_icon($n['type']),
    ];
}

echo json_encode([
    'ok' => true,
    'unread' => get_unread_count('customer', $customer['id']),
    'items' => $out,
]);

function notif_icon($type) {
    switch ($type) {
        case 'new_book':       return svg_icon('open-book', 'svg-icon icon-sm');
        case 'order_confirmed': return svg_icon('check-circle', 'svg-icon icon-sm');
        case 'order_rejected': return svg_icon('cross', 'svg-icon icon-sm');
        case 'review_approved': return svg_icon('star', 'svg-icon icon-sm');
        default:               return svg_icon('bell', 'svg-icon icon-sm');
    }
}
