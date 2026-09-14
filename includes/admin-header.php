<?php require_once __DIR__ . '/functions.php'; require_admin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? 'Admin') ?> · Juba Tech</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=28">
</head>
<body class="admin-body">

<!-- ============================================= -->
<!-- DESKTOP SIDEBAR (hidden on mobile via CSS)    -->
<!-- ============================================= -->
<aside class="admin-sidebar">
  <a href="dashboard.php" class="admin-brand">
    <span class="brand-icon"><?= svg_icon('open-book') ?></span>
    <span>
      <strong>Juba Tech</strong>
      <small>Admin Panel</small>
    </span>
  </a>
  <nav class="admin-nav">
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <span><?= svg_icon('chart') ?></span> Dashboard
    </a>
    <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-view.php' ? 'active' : '' ?>">
      <span><?= svg_icon('cart') ?></span> Orders
    </a>

    <?php if (file_exists(__DIR__ . '/admin-notification-bell.php')): ?>
      <?php include __DIR__ . '/admin-notification-bell.php'; ?>
    <?php endif; ?>

    <a href="products.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['products.php','product-edit.php']) ? 'active' : '' ?>">
      <span><?= svg_icon('open-book') ?></span> Products
    </a>
    <a href="subjects.php" class="<?= basename($_SERVER['PHP_SELF']) === 'subjects.php' ? 'active' : '' ?>">
      <span><?= svg_icon('folder') ?></span> Subjects
    </a>
    <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
      <span><?= svg_icon('star') ?></span> Reviews
    </a>
    <a href="blog.php" class="<?= basename($_SERVER['PHP_SELF']) === 'blog.php' ? 'active' : '' ?>">
      <span><?= svg_icon('edit') ?></span> Blog
    </a>
    <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
      <span><?= svg_icon('settings') ?></span> Settings
    </a>
  </nav>
  <a href="logout.php" class="admin-logout"><?= svg_icon('logout') ?> Logout</a>
</aside>

<!-- ============================================= -->
<!-- MOBILE DRAWER                                 -->
<!-- ============================================= -->
<div class="admin-mobile-drawer" id="adminMobileDrawer" aria-hidden="true">
  <div class="admin-mobile-overlay" data-admin-close></div>

  <div class="admin-mobile-panel">
    <div class="admin-mobile-head">
      <div class="admin-brand-mini">
        <span class="brand-icon"><?= svg_icon('open-book') ?></span>
        <div>
          <strong>Juba Tech</strong>
          <small>Admin Panel</small>
        </div>
      </div>
      <button type="button" class="admin-mobile-close" data-admin-close aria-label="Close menu">✕</button>
    </div>

    <nav class="admin-mobile-nav">
      <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
        <span><?= svg_icon('chart') ?></span> Dashboard
      </a>
      <a href="orders.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['orders.php','order-view.php']) ? 'active' : '' ?>">
        <span><?= svg_icon('cart') ?></span> Orders
      </a>
      <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
        <span><?= svg_icon('bell') ?></span> Notifications
        <?php
          $adminUnread = get_unread_count('admin', $_SESSION['admin_id'] ?? 0);
          if ($adminUnread > 0):
        ?>
          <span class="mobile-notif-badge"><?= $adminUnread > 99 ? '99+' : $adminUnread ?></span>
        <?php endif; ?>
      </a>
      <a href="products.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['products.php','product-edit.php']) ? 'active' : '' ?>">
        <span><?= svg_icon('open-book') ?></span> Products
      </a>
      <a href="subjects.php" class="<?= basename($_SERVER['PHP_SELF']) === 'subjects.php' ? 'active' : '' ?>">
        <span><?= svg_icon('folder') ?></span> Subjects
      </a>
      <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
        <span><?= svg_icon('star') ?></span> Reviews
      </a>
      <a href="blog.php" class="<?= basename($_SERVER['PHP_SELF']) === 'blog.php' ? 'active' : '' ?>">
        <span><?= svg_icon('edit') ?></span> Blog
      </a>
      <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
        <span><?= svg_icon('settings') ?></span> Settings
      </a>
    </nav>

    <a href="logout.php" class="admin-mobile-logout">
      <?= svg_icon('logout') ?> Logout
    </a>
  </div>
</div>

<!-- ============================================= -->
<!-- MAIN CONTENT                                  -->
<!-- ============================================= -->
<main class="admin-main">
<header class="admin-topbar">
  <button type="button" class="admin-mobile-toggle" id="adminMobileToggle" aria-label="Open menu">
    <span class="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </span>
  </button>

  <h1><?= clean($pageTitle ?? 'Admin') ?></h1>

  <div class="admin-user">
    <div class="avatar">A</div>
    <span>Admin</span>
  </div>
</header>
