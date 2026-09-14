<?php
require_once __DIR__ . '/includes/functions.php';

$bookId = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
if (!$bookId) redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND price_type = 'free' AND status = 'active'");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'This free guide is not available.', 'Not found');
    redirect('all-books.php');
}

// Not logged in? Send them to lead capture with a next pointer back here
if (!is_logged_in()) {
    redirect('lead-capture.php?next=' . urlencode('get-free.php?book_id=' . $bookId));
}

$customer = current_customer();

// Check if they already have it
$stmt = $pdo->prepare("
    SELECT o.id FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.customer_id = ? AND oi.book_id = ? AND o.status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$customer['id'], $bookId]);
$existing = $stmt->fetch();

if ($existing) {
    // Already have it, just go to downloads
    flash('info', 'You already have this free guide in your account.', 'Already yours');
    redirect('download.php?book_id=' . $bookId . '&order_id=' . $existing['id']);
}

// Create a free order
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, subtotal, discount_amount, coupon_code, status) VALUES (?, 0, 0, 0, NULL, 'confirmed')");
    $stmt->execute([$customer['id']]);
    $orderId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, price) VALUES (?, ?, 0)");
    $stmt->execute([$orderId, $bookId]);

    $pdo->commit();

    // Create a review prompt for later
    maybe_create_review_prompt($customer['id'], $bookId, $orderId);

    flash('success', 'Your free guide is ready to download.', 'Enjoy!');
    redirect('download.php?book_id=' . $bookId . '&order_id=' . $orderId);

} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', 'Something went wrong. Please try again.', 'Error');
    redirect('sales.php?book_id=' . $bookId);
}
