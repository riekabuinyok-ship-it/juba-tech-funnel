<?php
require __DIR__ . '/../includes/db.php';
try {
    $pdo->query("SELECT 1");
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'time' => date('c')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
