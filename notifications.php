<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();

$customer = current_customer();
$items = get_notifications('customer', $customer['id'], 50);

// Mark all as read when the page opens
mark_customer_notifications_read($customer['id']);

$pageTitle = 'Notifications';
include __DIR__ . '/includes/header.php';
?>

<div class="notif-page">
  <h1>Notifications</h1>

  <?php if (empty($items)): ?>
    <div class="card empty-state">
      <div class="empty-emoji"><?= svg_icon('bell') ?></div>
      <h3>All caught up</h3>
      <p>You will see updates about new guides and your orders here.</p>
    </div>
  <?php else: ?>
    <div class="notif-list">
      <?php foreach ($items as $n): ?>
        <?php
          $icon = svg_icon('bell');
          if ($n['type'] === 'new_book') $icon = svg_icon('open-book');
          if ($n['type'] === 'order_confirmed') $icon = svg_icon('check');
          if ($n['type'] === 'order_rejected') $icon = svg_icon('cross');
          if ($n['type'] === 'review_approved') $icon = svg_icon('star');
        ?>
        <div class="notif-row notif-<?= clean($n['type']) ?>">
          <div class="notif-row-icon"><?= $icon ?></div>
          <div class="notif-row-body">
            <strong><?= clean($n['title']) ?></strong>
            <p><?= clean($n['message']) ?></p>
            <small><?= date('M j, Y · g:i A', strtotime($n['created_at'])) ?></small>
          </div>
          <?php if (!empty($n['link'])): ?>
            <a href="<?= clean($n['link']) ?>" class="notif-row-cta">Open →</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
