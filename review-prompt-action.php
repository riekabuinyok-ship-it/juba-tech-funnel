<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$promptId = (int)($_POST['prompt_id'] ?? 0);

if (!$promptId) {
    echo json_encode(['ok' => false, 'error' => 'Missing prompt ID']);
    exit;
}

// Make sure the prompt belongs to this customer
$customer = current_customer();
$stmt = $pdo->prepare("SELECT id FROM review_prompts WHERE id = ? AND customer_id = ?");
$stmt->execute([$promptId, $customer['id']]);
if (!$stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Prompt not found']);
    exit;
}

if ($action === 'snooze') {
    snooze_review_prompt($promptId, 7);
    echo json_encode(['ok' => true, 'message' => 'We will remind you in 7 days.']);
    exit;
}

if ($action === 'dismiss') {
    dismiss_review_prompt($promptId);
    echo json_encode(['ok' => true, 'message' => 'Got it. We will not ask again.']);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);
