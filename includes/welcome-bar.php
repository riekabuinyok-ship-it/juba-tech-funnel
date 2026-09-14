<?php
// Show only on the homepage and only if enabled
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'index.php') return;
if (setting('welcome_bar_enabled', '1') !== '1') return;

// Do not show if user dismissed it or is logged in
if (isset($_COOKIE['welcome_bar_dismissed'])) return;
if (function_exists('is_logged_in') && is_logged_in()) return;

$text   = setting('welcome_bar_text', 'New here? Get 10% off your first study guide.');
$code   = setting('welcome_bar_code', '');
$link   = setting('welcome_bar_link', 'signup.php');
$button = setting('welcome_bar_button', 'Claim Now');
?>
<div class="welcome-bar" id="welcomeBar" role="region" aria-label="Welcome offer">
  <div class="welcome-bar-content">
    <span class="welcome-bar-icon"><?= svg_icon('party') ?></span>
    <span class="welcome-bar-text">
      <?= clean($text) ?>
      <?php if ($code): ?>
        &nbsp;Use code <strong class="welcome-bar-code"><?= clean($code) ?></strong>
      <?php endif; ?>
    </span>
  </div>

  <div class="welcome-bar-actions">
    <a href="<?= SITE_URL ?>/<?= clean($link) ?>" class="welcome-bar-cta">
      <?= clean($button) ?>
      <span class="welcome-bar-arrow">→</span>
    </a>
    <button type="button" class="welcome-bar-close" id="welcomeBarClose" aria-label="Dismiss welcome offer">
      ✕
    </button>
  </div>
</div>
