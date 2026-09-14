<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) redirect('orders.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'confirm') {
    $pdo->prepare("UPDATE orders SET status='confirmed' WHERE id=?")->execute([$orderId]);
    $pdo->prepare("UPDATE payments SET status='confirmed', confirmed_at=NOW() WHERE order_id=?")->execute([$orderId]);

    // Notify the customer
    $stmt = $pdo->prepare("SELECT customer_id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $customerId = $stmt->fetchColumn();

    if ($customerId) {
        notify(
            'customer',
            $customerId,
            'order_confirmed',
            'Payment Confirmed',
            'Your order #' . $orderId . ' is confirmed. Your download links are unlocked.',
            'account.php'
        );
    }
} elseif ($action === 'reject') {
    $pdo->prepare("UPDATE orders SET status='rejected' WHERE id=?")->execute([$orderId]);
    $pdo->prepare("UPDATE payments SET status='rejected' WHERE order_id=?")->execute([$orderId]);

    $stmt = $pdo->prepare("SELECT customer_id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $customerId = $stmt->fetchColumn();

    if ($customerId) {
        notify(
            'customer',
            $customerId,
            'order_rejected',
            'Payment Not Verified',
            'We could not verify your payment for order #' . $orderId . '. Please contact support.',
            'account.php'
        );
    }
}
    redirect('orders.php');
}

$stmt = $pdo->prepare("
  SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.location,
         p.transaction_id, p.momo_account_name, p.momo_account_number,
         p.status AS payment_status
  FROM orders o
  JOIN customers c ON c.id = o.customer_id
  LEFT JOIN payments p ON p.order_id = o.id
  WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) redirect('orders.php');

$stmt = $pdo->prepare("
  SELECT oi.*, b.title, b.type FROM order_items oi
  JOIN books b ON b.id = oi.book_id WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$pageTitle = 'Order #' . $orderId;
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <a href="orders.php" class="btn btn-outline btn-sm">← Back to Orders</a>
  <?php if ($order['status'] === 'pending'): ?>
    <form method="post" style="display:inline-flex;gap:10px;" onsubmit="return confirm('Confirm this payment and unlock downloads?')">
      <button name="action" value="confirm" class="btn">Confirm Payment</button>
      <button name="action" value="reject" class="btn btn-outline">Reject Payment</button>
    </form>
  <?php endif; ?>
</div>

<section class="admin-section">
  <div class="section-head">
    <h2>Customer & Payment</h2>
    <span class="status-badge status-<?= $order['status'] ?>">
      <?= ucfirst($order['status']) ?>
    </span>
  </div>
  <p><strong>Customer:</strong> <?= clean($order['customer_name']) ?></p>
  <p><strong>Email:</strong> <?= clean($order['customer_email']) ?></p>
  <p><strong>Location:</strong> <?= clean($order['location'] ?: '—') ?></p>
  <p><strong>MoMo Name:</strong> <?= clean($order['momo_account_name'] ?: '—') ?></p>
  <p><strong>MoMo Number:</strong> <?= clean($order['momo_account_number'] ?: '—') ?></p>
  <p><strong>Transaction ID:</strong> <?= clean($order['transaction_id'] ?: '—') ?></p>
  <p><strong>Total:</strong> <?= money($order['total_amount']) ?></p>
  <p><strong>Placed:</strong> <?= date('M j, Y · g:i A', strtotime($order['created_at'])) ?></p>
</section>

<section class="admin-section">
  <div class="section-head">
    <h2>Items</h2>
    <span class="count-pill"><?= count($items) ?> items</span>
  </div>
  <div class="books-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Book</th>
          <th>Type</th>
          <th>Price</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><strong><?= clean($item['title']) ?></strong></td>
            <td>
              <span class="badge badge-<?= $item['type'] === 'bundle' ? 'bundle' : 'single' ?>">
                <?= $item['type'] === 'bundle' ? 'Bundle' : 'Single' ?>
              </span>
            </td>
            <td><?= money($item['price']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>