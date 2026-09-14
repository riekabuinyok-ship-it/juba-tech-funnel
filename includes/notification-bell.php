<?php
if (!is_logged_in()) return;
$customer = current_customer();
if (!$customer) return;

$unread = get_unread_count('customer', $customer['id']);
?>
<div class="notif-bell-wrap" id="notifBellWrap">
  <button type="button" class="notif-bell" id="notifBell" aria-label="Notifications">
    <?= svg_icon('bell') ?>
    <?php if ($unread > 0): ?>
      <span class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
    <?php endif; ?>
  </button>

  <div class="notif-panel" id="notifPanel" aria-hidden="true">
    <div class="notif-panel-head">
      <strong>Notifications</strong>
      <a href="notifications.php" class="notif-view-all">View all</a>
    </div>
    <div class="notif-panel-body" id="notifPanelBody">
      <div class="notif-loading">Loading…</div>
    </div>
  </div>
</div>
