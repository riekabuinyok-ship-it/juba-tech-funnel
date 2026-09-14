<?php
// Only on the sales page
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'sales.php') return;

// Only if a coupon is configured for exit intent
$exitCode = setting('exit_intent_coupon', 'WELCOME10');
if (!$exitCode) return;

// Skip if user is already logged in (they are already a customer)
if (function_exists('is_logged_in') && is_logged_in()) return;

$discountLabel = setting('exit_intent_discount_label', '10% OFF');
$heading = setting('exit_intent_heading', 'Wait! Do not leave yet');
$message = setting('exit_intent_message', 'Here is a special discount just for you.');
?>

<div class="exit-modal" id="exitModal" aria-hidden="true">
  <div class="exit-modal-overlay" data-exit-close></div>

  <div class="exit-modal-panel" role="dialog" aria-labelledby="exitTitle">
    <button type="button" class="exit-modal-close" data-exit-close aria-label="Close">✕</button>

    <div class="exit-modal-badge"><?= clean($discountLabel) ?></div>

    <div class="exit-modal-icon"><?= svg_icon('gift') ?></div>

    <h2 id="exitTitle" class="exit-modal-title"><?= clean($heading) ?></h2>
    <p class="exit-modal-text"><?= clean($message) ?></p>

    <div class="exit-modal-code">
      <span class="exit-code-label">Use code</span>
      <strong class="exit-code-value" id="exitCode"><?= clean($exitCode) ?></strong>
      <button type="button" class="exit-copy-btn" data-copy="<?= clean($exitCode) ?>" aria-label="Copy code">
        <?= svg_icon('receipt') ?>
      </button>
    </div>

    <a href="<?= SITE_URL ?>/checkout-continue.php?code=<?= urlencode($exitCode) ?>&return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '') ?>" class="btn btn-large btn-block exit-cta">
      Claim Discount &amp; Continue →
    </a>

    <button type="button" class="exit-decline" data-exit-close>
      No thanks, I will pay full price
    </button>
  </div>
</div>
