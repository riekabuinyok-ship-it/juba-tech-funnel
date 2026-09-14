<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();
$customer = current_customer();

// Fetch orders with items
$stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$customer['id']]);
$orders = $stmt->fetchAll();

// Stats
$totalOrders = count($orders);
$totalSpent = 0;
$totalItems = 0;
$unlockedItems = 0;
foreach ($orders as $o) {
    $totalSpent += $o['total_amount'];
    $stmt2 = $pdo->prepare("SELECT COUNT(*) AS c FROM order_items WHERE order_id = ?");
    $stmt2->execute([$o['id']]);
    $count = (int)$stmt2->fetchColumn();
    $totalItems += $count;
    if ($o['status'] === 'confirmed') {
        $unlockedItems += $count;
    }
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="account-hero">
  <div class="account-identity">
    <div class="account-avatar">
      <?= strtoupper(substr($customer['name'], 0, 1)) ?>
    </div>
    <div class="account-meta">
      <h1><?= clean($customer['name']) ?></h1>
      <p>
        <span><?= svg_icon('envelope') ?> <?= clean($customer['email']) ?></span>
        <?php if (!empty($customer['class_level'])): ?>
          <span><?= svg_icon('graduation-cap') ?> <?= clean($customer['class_level']) ?></span>
        <?php endif; ?>
        <?php if (!empty($customer['location'])): ?>
          <span><?= svg_icon('map-pin') ?> <?= clean($customer['location']) ?></span>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div class="account-stats">
    <div class="stat-tile">
      <strong><?= $totalOrders ?></strong>
      <small>Orders</small>
    </div>
    <div class="stat-tile">
      <strong><?= $totalItems ?></strong>
      <small>Books</small>
    </div>
    <div class="stat-tile">
      <strong><?= $unlockedItems ?></strong>
      <small>Unlocked</small>
    </div>
    <div class="stat-tile">
      <strong><?= money($totalSpent) ?></strong>
      <small>Total Spent</small>
    </div>
  </div>
</div>

<div class="section-head account-head">
  <h2>My Orders</h2>
  <a href="subjects.php?level=secondary" class="btn btn-sm">+ Buy More Guides</a>
</div>

<?php if (empty($orders)): ?>
  <div class="card empty-state">
    <div class="empty-emoji"><?= svg_icon('open-book', 'icon-xl') ?></div>
    <h3>No orders yet</h3>
    <p>You have not purchased any study guides. Browse our collection and start learning today.</p>
    <a href="subjects.php?level=secondary" class="btn btn-large">Browse Study Guides</a>
  </div>
<?php else: ?>
  <div class="orders-list">
    <?php foreach ($orders as $order): ?>
      <?php
        $statusClass = $order['status'] === 'confirmed' ? 'confirmed' : ($order['status'] === 'rejected' ? 'rejected' : 'pending');
        $statusLabel = ucfirst($order['status']);
        $statusIcon = $order['status'] === 'confirmed' ? svg_icon('check-circle') : ($order['status'] === 'rejected' ? svg_icon('cross') : svg_icon('clock'));

        $stmt2 = $pdo->prepare("
          SELECT oi.*, b.title, b.cover_image, b.type
          FROM order_items oi
          JOIN books b ON b.id = oi.book_id
          WHERE oi.order_id = ?
        ");
        $stmt2->execute([$order['id']]);
        $items = $stmt2->fetchAll();
      ?>
      <div class="order-card">
        <div class="order-head">
          <div>
            <div class="order-id">Order #<?= $order['id'] ?></div>
            <div class="order-date">
              <?= date('M j, Y · g:i A', strtotime($order['created_at'])) ?>
            </div>
          </div>
          <div class="order-head-right">
            <span class="order-price"><?= money($order['total_amount']) ?></span>
            <span class="status-badge status-<?= $statusClass ?>">
              <?= $statusIcon ?> <?= $statusLabel ?>
            </span>
          </div>
        </div>

        <?php if ($order['status'] === 'pending'): ?>
          <div class="order-note note-pending">
            <?= svg_icon('clock') ?> Payment under review. Your downloads will unlock once the admin confirms your MoMo transaction.
          </div>
        <?php elseif ($order['status'] === 'rejected'): ?>
          <div class="order-note note-rejected">
            <?= svg_icon('cross') ?> Payment could not be verified. Please contact support at <?= clean(setting('contact_email')) ?>.
          </div>
        <?php endif; ?>

        <ul class="order-items">
          <?php foreach ($items as $item): ?>
            <li class="order-item">
              <div class="item-cover">
                <?php if (!empty($item['cover_image'])): ?>
                  <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($item['cover_image']) ?>" alt="">
                <?php else: ?>
                  <?= $item['type'] === 'bundle' ? svg_icon('package') : svg_icon('open-book') ?>
                <?php endif; ?>
              </div>
              <div class="item-info">
                <div class="item-title"><?= clean($item['title']) ?></div>
                <div class="item-price"><?= money($item['price']) ?></div>
              </div>
              <div class="item-action">
                <?php if ($order['status'] === 'confirmed'): ?>
                  <div style="display:flex;gap:8px;align-items:center;">
                    <a href="download.php?book_id=<?= $item['book_id'] ?>&order_id=<?= $order['id'] ?>" class="btn" style="padding:6px 14px;font-size:0.82rem;"><?= svg_icon('download') ?> Download</a>
                    <?php
                      $stmt = $pdo->prepare("SELECT id FROM reviews WHERE book_id = ? AND customer_id = ?");
                      $stmt->execute([$item['book_id'], $customer['id']]);
                      $has_review = $stmt->fetch();
                    ?>
                    <?php if (!$has_review): ?>
                      <a href="write-review.php?book_id=<?= $item['book_id'] ?>&order_id=<?= $order['id'] ?>" class="btn btn-outline" style="padding:6px 14px;font-size:0.82rem;"><?= svg_icon('star') ?> Review</a>
                    <?php else: ?>
                      <span style="font-size:0.78rem;color:var(--green);font-weight:700;">✓ Reviewed</span>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="locked-pill"><?= svg_icon('lock') ?> Locked</span>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>