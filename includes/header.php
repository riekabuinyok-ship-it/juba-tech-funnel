<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? SITE_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/images/logo-icon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=28">
</head>
<body>

<?php include __DIR__ . '/welcome-bar.php'; ?>

<?php include __DIR__ . '/topbar.php'; ?>

<header class="site-header">
  <div class="container">

    <!-- Logo -->
    <a href="<?= SITE_URL ?>/index.php" class="logo">
      <img src="<?= SITE_URL ?>/assets/images/logo.svg"
           alt="<?= clean(setting('site_name', SITE_NAME)) ?>"
           class="logo-svg logo-full">
      <img src="<?= SITE_URL ?>/assets/images/logo-icon.svg"
           alt="<?= clean(setting('site_name', SITE_NAME)) ?>"
           class="logo-svg logo-icon-only">
    </a>

    <!-- Search bar -->
    <?php $currentQ = $_GET['q'] ?? ''; ?>
    <form class="header-search" method="get" action="<?= SITE_URL ?>/all-books.php" role="search">
      <input type="text" name="q" placeholder="Search study guides..."
             value="<?= clean($currentQ) ?>" autocomplete="off" aria-label="Search study guides">
      <button type="submit" aria-label="Search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </form>

    <!-- Desktop navigation -->
    <nav class="main-nav">
  <a href="<?= SITE_URL ?>/index.php">Home</a>
  <a href="<?= SITE_URL ?>/all-books.php">All Books</a>
  <a href="<?= SITE_URL ?>/subjects.php?level=secondary">Secondary</a>
  <a href="<?= SITE_URL ?>/subjects.php?level=primary">Primary</a>

  <?php if (is_logged_in()): ?>
    <a href="<?= SITE_URL ?>/account.php">My Account</a>
    <a href="<?= SITE_URL ?>/logout.php" class="btn-nav">Logout</a>
  <?php else: ?>
    <a href="<?= SITE_URL ?>/login.php">Login</a>
    <a href="<?= SITE_URL ?>/signup.php" class="btn-nav">Sign Up</a>
  <?php endif; ?>
</nav>

<?php include __DIR__ . '/notification-bell.php'; ?>

    <!-- Mobile hamburger -->
    <button class="mobile-toggle" aria-label="Open menu" aria-expanded="false">
      <span class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </span>
    </button>

  </div>
</header>

<!-- ============================================== -->
<!-- MOBILE DRAWER — MUST BE OUTSIDE THE <header>  -->
<!-- ============================================== -->
<div class="mobile-drawer" aria-hidden="true">
  <div class="mobile-drawer-overlay"></div>
  <div class="mobile-drawer-panel">
    <div class="mobile-drawer-head">
      <span class="mobile-drawer-title">Menu</span>
      <button class="mobile-close" aria-label="Close menu">✕</button>
    </div>

    <div class="mobile-drawer-body">
      <?php if (is_logged_in()): ?>
        <?php $cust = current_customer(); ?>
        <div class="mobile-user">
          <div class="mobile-user-avatar"><?= strtoupper(substr($cust['name'] ?? 'U', 0, 1)) ?></div>
          <div>
            <strong><?= clean($cust['name'] ?? '') ?></strong>
            <small><?= clean($cust['email'] ?? '') ?></small>
          </div>
        </div>
      <?php endif; ?>

<?php if (is_logged_in()): ?>
  <?php $unreadCount = get_unread_count('customer', current_customer()['id']); ?>
  <a href="<?= SITE_URL ?>/notifications.php" class="mobile-link">
    <span><?= svg_icon('bell') ?></span> Notifications
    <?php if ($unreadCount > 0): ?>
      <span class="mobile-notif-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
    <?php endif; ?>
  </a>
<?php endif; ?>

      <a href="<?= SITE_URL ?>/index.php" class="mobile-link">
        <span><?= svg_icon('home') ?></span> Home
      </a>
      <a href="<?= SITE_URL ?>/subjects.php?level=secondary" class="mobile-link">
        <span><?= svg_icon('graduation-cap') ?></span> Secondary Guides
      </a>
      <a href="<?= SITE_URL ?>/subjects.php?level=primary" class="mobile-link">
        <span><?= svg_icon('backpack') ?></span> Primary Guides
      </a>
      <a href="<?= SITE_URL ?>/how-it-works.php" class="mobile-link">
        <span><?= svg_icon('settings') ?></span> How It Works
      </a>

      <div class="mobile-divider"></div>

      <?php if (is_logged_in()): ?>
        <a href="<?= SITE_URL ?>/account.php" class="mobile-link">
          <span><?= svg_icon('users') ?></span> Dashboard
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="mobile-link mobile-link-danger">
          <span><?= svg_icon('logout') ?></span> Logout
        </a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="mobile-link">
          <span><?= svg_icon('users') ?></span> Login
        </a>
        <a href="<?= SITE_URL ?>/signup.php" class="mobile-link mobile-link-primary">
          <span><?= svg_icon('users') ?></span> Create Account
        </a>
      <?php endif; ?>

      <div class="mobile-divider"></div>

      <div class="mobile-contact">
        <p><strong>Need help?</strong></p>
        <a href="tel:<?= clean(setting('momo_account_number')) ?>"><?= svg_icon('phone') ?> <?= clean(setting('momo_account_number')) ?></a>
        <a href="mailto:<?= clean(setting('contact_email')) ?>"><?= svg_icon('envelope') ?> <?= clean(setting('contact_email')) ?></a>
      </div>
    </div>
  </div>
</div>
<!-- END MOBILE DRAWER -->

<main class="container">
