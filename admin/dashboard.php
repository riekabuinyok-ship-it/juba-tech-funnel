<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$stats = [
    'sales_today' => $pdo->query("
        SELECT COALESCE(SUM(total_amount),0) FROM orders
        WHERE DATE(created_at) = CURRENT_DATE
          AND status = 'confirmed'
          AND total_amount > 0
    ")->fetchColumn(),

    'sales_month' => $pdo->query("
        SELECT COALESCE(SUM(total_amount),0) FROM orders
        WHERE date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE)
          AND status = 'confirmed'
          AND total_amount > 0
    ")->fetchColumn(),

    'pending' => $pdo->query("
        SELECT COUNT(*) FROM orders
        WHERE status = 'pending' AND total_amount > 0
    ")->fetchColumn(),

    'confirmed' => $pdo->query("
        SELECT COUNT(*) FROM orders
        WHERE status = 'confirmed' AND total_amount > 0
    ")->fetchColumn(),

    'customers' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
];

$recentOrders = $pdo->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, c.name AS customer_name, c.class_level,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    JOIN customers c ON c.id = o.customer_id
    WHERE (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) > 0
    ORDER BY o.created_at DESC
    LIMIT 8
")->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">Store overview and recent activity.</p>
</div>

<div class="grid">
  <div class="stat-tile"><strong><?= money($stats['sales_today']) ?></strong><small>Sales Today</small></div>
  <div class="stat-tile"><strong><?= money($stats['sales_month']) ?></strong><small>Sales This Month</small></div>
  <div class="stat-tile"><strong><?= $stats['pending'] ?></strong><small>Pending Orders</small></div>
  <div class="stat-tile"><strong><?= $stats['confirmed'] ?></strong><small>Confirmed Orders</small></div>
  <div class="stat-tile"><strong><?= $stats['customers'] ?></strong><small>Customers</small></div>
</div>

<section class="admin-section">
  <div class="section-head">
    <h2>Recent Orders</h2>
    <span class="count-pill">Latest 8</span>
  </div>
  <?php if (empty($recentOrders)): ?>
    <p class="muted">No orders yet.</p>
  <?php else: ?>
    <div class="books-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td>#<?= $o['id'] ?></td>
              <td>
                <strong><?= clean($o['customer_name']) ?></strong>
                <?php if (!empty($o['class_level'])): ?>
                  <small style="display:block;color:var(--text-muted);font-size:0.72rem;"><?= clean($o['class_level']) ?></small>
                <?php endif; ?>
              </td>
              <td><strong><?= money($o['total_amount']) ?></strong></td>
              <td>
                <span class="status-badge status-<?= $o['status'] ?>">
                  <?= ucfirst($o['status']) ?>
                </span>
              </td>
              <td class="muted"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
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