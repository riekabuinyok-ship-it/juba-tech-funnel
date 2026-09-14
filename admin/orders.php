<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$orders = $pdo->query("
  SELECT o.*, c.name AS customer_name, c.email AS customer_email,
         p.transaction_id, p.status AS payment_status
  FROM orders o
  JOIN customers c ON c.id = o.customer_id
  LEFT JOIN payments p ON p.order_id = o.id
  ORDER BY o.created_at DESC
")->fetchAll();

$pendingCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$pageTitle = 'Orders';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">Review customer orders and confirm MoMo payments.</p>
</div>

<section class="admin-section">
  <div class="section-head">
    <h2>All Orders</h2>
    <span class="count-pill"><?= count($orders) ?> orders <?= $pendingCount ? '· ' . $pendingCount . ' pending' : '' ?></span>
  </div>
  <?php if (empty($orders)): ?>
    <p class="muted">No orders yet.</p>
  <?php else: ?>
    <div class="books-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Transaction</th>
            <th>Status</th>
            <th>Order</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= $o['id'] ?></td>
              <td>
                <strong><?= clean($o['customer_name']) ?></strong>
                <br><small class="muted"><?= clean($o['customer_email']) ?></small>
              </td>
              <td><strong><?= money($o['total_amount']) ?></strong></td>
              <td><?= clean($o['transaction_id'] ?? '—') ?></td>
              <td>
                <span class="status-badge status-<?= $o['status'] ?>">
                  <?= ucfirst($o['status']) ?>
                </span>
              </td>
              <td class="row-actions">
                <a href="order-view.php?id=<?= $o['id'] ?>" class="btn btn-sm">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>