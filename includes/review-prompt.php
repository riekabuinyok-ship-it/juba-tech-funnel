<?php
require_once __DIR__ . '/functions.php';

// Only for logged-in customers
if (!is_logged_in()) return;

// Do not show on the write-review page itself or the review-prompt action page
$currentPage = basename($_SERVER['PHP_SELF']);
if (in_array($currentPage, ['write-review.php', 'review-prompt-action.php'])) return;

$customer = current_customer();
if (!$customer) return;

// Bring any snoozed prompts back
refresh_review_prompts($customer['id']);

$prompt = get_pending_review_prompt($customer['id']);
if (!$prompt) return;

// Mark as shown now
mark_review_prompt_shown($prompt['id']);
?>
<div class="review-prompt" id="reviewPrompt" aria-hidden="true"
     data-prompt-id="<?= (int)$prompt['id'] ?>"
     data-book-id="<?= (int)$prompt['book_id'] ?>"
     data-order-id="<?= (int)$prompt['order_id'] ?>">

  <div class="review-prompt-overlay" data-review-close></div>

  <div class="review-prompt-panel" role="dialog" aria-labelledby="reviewPromptTitle">
    <button type="button" class="review-prompt-close" data-review-close aria-label="Close">✕</button>

    <div class="review-prompt-icon"><?= svg_icon('star') ?></div>

    <h2 id="reviewPromptTitle" class="review-prompt-title">
      How was the <?= clean($prompt['book_title']) ?>?
    </h2>
    <p class="review-prompt-text">
      Your feedback helps other South Sudan students choose the right guides. Would you leave a quick review?
    </p>

    <div class="review-prompt-book">
      <div class="review-prompt-cover">
        <?php if (!empty($prompt['cover_image'])): ?>
          <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($prompt['cover_image']) ?>" alt="">
        <?php else: ?>
          <?= svg_icon('open-book') ?>
        <?php endif; ?>
      </div>
      <div>
        <strong><?= clean($prompt['book_title']) ?></strong>
        <small>Verified purchase</small>
      </div>
    </div>

    <div class="review-prompt-actions">
      <a href="write-review.php?book_id=<?= (int)$prompt['book_id'] ?>&order_id=<?= (int)$prompt['order_id'] ?>&prompt_id=<?= (int)$prompt['id'] ?>"
         class="btn btn-large btn-block">
        <?= svg_icon('star') ?> Write a Review
      </a>

      <div class="review-prompt-secondary">
        <button type="button" class="review-prompt-link" data-review-snooze>
          Maybe later
        </button>
        <span class="review-prompt-dot">·</span>
        <button type="button" class="review-prompt-link" data-review-dismiss>
          Do not ask again
        </button>
      </div>
    </div>
  </div>
</div>
