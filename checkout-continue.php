<?php
require_once __DIR__ . '/includes/functions.php';

$code = strtoupper(trim($_GET['code'] ?? setting('exit_intent_coupon', 'WELCOME10')));

// Validate the coupon
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if ($coupon) {
    $_SESSION['pending_coupon'] = $code;
    flash('success', 'Coupon ' . $code . ' is ready. It will apply at checkout.', 'Discount saved');
} else {
    flash('warning', 'That coupon is not available.', 'Notice');
}

$returnTo = $_GET['return'] ?? ($_SERVER['HTTP_REFERER'] ?? 'all-books.php');
redirect($returnTo);
