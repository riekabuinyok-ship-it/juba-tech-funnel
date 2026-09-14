<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$adminId = $_SESSION['admin_id'];

// Mark all as read when this page is opened
mark_all_admin_notifications_read($adminId);

$items = get_notifications('admin', $adminId, 100);

$pageTitle = 'Notifications';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">All notifications for your admin account.</p>
</div>

<?php if (empty($items)): ?>
  <div class="card" style="text-align:center;padding:60px 30px;">
    <div style="font-size:3.5rem;margin-bottom:12px;"><?= svg_icon('bell') ?></div>
    <h3 style="font-weight:800;">No notifications yet</h3>
    <p style="color:var(--text-muted);">New orders and updates will show up here.</p>
  </div>
<?php else: ?>
  <div class="notif-list">
    <?php foreach ($items as $n): ?>
      <?php
        $icon = svg_icon('bell');
        if ($n['type'] === 'new_order') $icon = svg_icon('cart');
        if ($n['type'] === 'order_confirmed') $icon = svg_icon('check');
        if ($n['type'] === 'new_review') $icon = svg_icon('star');
      ?>
      <div class="notif-row notif-<?= clean($n['type']) ?> <?= $n['is_read'] ? 'is-read' : 'is-unread' ?>">
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

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
