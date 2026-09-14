<?php
if (!is_admin()) return;
$adminId = $_SESSION['admin_id'];
$unread = get_unread_count('admin', $adminId);
?>
<a href="notifications.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
  <span><?= svg_icon('bell') ?></span> Notifications
  <?php if ($unread > 0): ?>
    <span class="admin-notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
  <?php endif; ?>
</a>
