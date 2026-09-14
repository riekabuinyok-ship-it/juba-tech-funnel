<?php
require_once __DIR__ . '/includes/functions.php';

$bookId = (int)($_GET['book_id'] ?? 0);
if (!$bookId) redirect('index.php');

// NEW: If not logged in AND no lead yet, send them to lead-capture with a next parameter
if (!is_logged_in() && empty($_SESSION['lead'])) {
    redirect('lead-capture.php?next=' . urlencode('sales.php?book_id=' . $bookId));
}

$stmt = $pdo->prepare("SELECT b.*, s.name AS subject_name, s.icon AS subject_icon FROM books b JOIN subjects s ON s.id = b.subject_id WHERE b.id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();
if (!$book) redirect('index.php');

// Reset cart to only this book (fresh purchase flow)
set_cart([$bookId]);
$_SESSION['primary_book_id'] = $bookId;
$_SESSION['primary_subject_id'] = $book['subject_id'];

$pageTitle = clean($book['title']);
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step active">2. Book</div>
  <div class="step">3. Payment</div>
  <div class="step">4. Download</div>
</div>

<div class="breadcrumb">
  <a href="index.php">Home</a>
  <span>›</span>
  <a href="subjects.php?level=secondary">Subjects</a>
  <span>›</span>
  <strong><?= clean($book['title']) ?></strong>
</div>

<div class="sales-layout">

  <!-- LEFT: Book details -->
  <div class="card">
    <div class="sales-cover">
      <?php if (!empty($book['cover_image'])): ?>
        <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($book['cover_image']) ?>"
             alt="<?= clean($book['title']) ?>"
             class="sales-cover-img">
      <?php else: ?>
        <span class="sales-cover-emoji" style="color:var(--coral);"><?= subject_svg($book['subject_name'], 'svg-icon icon-xl') ?></span>
      <?php endif; ?>
      <span class="product-badge"><?= $book['type'] === 'bundle' ? 'Bundle' : 'Single Book' ?></span>
    </div>

    <h1 style="font-size:1.9rem;font-weight:800;letter-spacing:-0.5px;margin:12px 0 6px;">
      <?= clean($book['title']) ?>
    </h1>
    <p style="color:var(--text-muted);margin-bottom:16px;">
      <?= clean($book['description'] ?: 'Complete pack with all chapters aligned to the South Sudan curriculum.') ?>
    </p>

    <?php
      $rating = get_book_rating($book['id']);
      $reviews = get_book_reviews($book['id'], 3);
      $badge = get_book_badge($book['id']);
    ?>
    <div class="sales-rating-row">
      <?php if ($rating['total'] > 0): ?>
        <?= render_stars($rating['average'], $rating['total'], 'md') ?>
      <?php else: ?>
        <span class="badge badge-new">New Release</span>
        <span class="sales-rating-hint">Be the first to review this guide</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($book['preview_url'])): ?>
      <button type="button" class="btn btn-preview" data-preview="<?= clean($book['preview_url']) ?>" data-title="<?= clean($book['title']) ?>">
        <span class="preview-icon"><?= svg_icon('eye') ?></span>
        <span>
          <strong>Preview This Guide</strong>
          <small>See the first few pages before you buy</small>
        </span>
        <span class="preview-arrow">→</span>
      </button>
    <?php endif; ?>

    <?= render_trust_strip($book['id']) ?>

    <h3 style="font-size:1rem;margin:20px 0 12px;">What You Get</h3>
    <ul class="feature-list">
      <li><?= svg_icon('check-circle') ?> Full access to <?= clean($book['title']) ?></li>
      <li><?= svg_icon('check-circle') ?> Curriculum-aligned content</li>
      <li><?= svg_icon('check-circle') ?> Instant download after payment confirmation</li>
      <li><?= svg_icon('check-circle') ?> Works on phone, tablet, and computer</li>
      <li><?= svg_icon('check-circle') ?> 24-hour secure download link</li>
    </ul>

    <?php if (!empty($reviews)): ?>
      <div style="margin-top:32px;">
        <h3 style="font-size:1rem;margin-bottom:14px;">What Students Say</h3>
        <div class="review-list">
          <?php foreach ($reviews as $r): ?>
            <div class="review-card">
              <div class="review-head">
                <div class="review-avatar"><?= strtoupper(substr($r['customer_name'], 0, 1)) ?></div>
                <div>
                  <strong><?= clean($r['customer_name']) ?></strong>
                  <small>
                    <?= clean($r['class_level']) ?>
                    · <?= date('M j, Y', strtotime($r['created_at'])) ?>
                  </small>
                </div>
                <div class="review-stars"><?= render_stars($r['rating'], null, 'sm') ?></div>
              </div>
              <?php if (!empty($r['title'])): ?>
                <h4 class="review-title"><?= clean($r['title']) ?></h4>
              <?php endif; ?>
              <p class="review-comment"><?= nl2br(clean($r['comment'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Payment panel -->
  <div>
    <div class="card" style="position:sticky;top:90px;">
      <div style="display:flex;justify-content:space-between;align-items:baseline;">
        <span style="color:var(--text-muted);font-size:0.85rem;">Total Price</span>
        <span style="font-size:0.72rem;background:var(--green);color:white;padding:3px 10px;border-radius:999px;font-weight:700;">IN STOCK</span>
      </div>
<?php if (($book['price_type'] ?? 'paid') === 'free'): ?>
  <div class="price-big price-free" style="margin:4px 0 20px;">FREE</div>
<?php else: ?>
  <div class="price-big" style="margin:4px 0 20px;"><?= money($book['price']) ?></div>
<?php endif; ?>

<?php if (($book['price_type'] ?? 'paid') === 'free'): ?>
  <div class="free-notice">
    <strong><?= svg_icon('gift') ?> This guide is completely free.</strong>
    <p>Create a free account and download it right away. No payment, no MoMo, no waiting.</p>
  </div>

  <form method="post" action="get-free.php">
    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
    <button type="submit" class="btn btn-large btn-block" style="margin-top:16px;">
      Get It for Free →
    </button>
  </form>
<?php else: ?>
      <div class="payment-box">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <img src="<?= SITE_URL ?>/assets/img/momo.png" alt="MoMo" style="height:34px;width:auto;">
          <strong style="font-size:1rem;">Pay with MoMo</strong>
        </div>

        <div class="label">Account Name</div>
        <div class="value"><?= clean(setting('momo_account_name')) ?></div>

        <div class="label">Account Number</div>
        <div class="value"><?= clean(setting('momo_account_number')) ?></div>

        <div class="label">Amount to Send</div>
        <div class="value" style="color:var(--coral);font-size:1.5rem;font-weight:800;">
          <?= money($book['price']) ?>
        </div>

        <p style="font-size:0.85rem;color:var(--text-muted);margin-top:12px;">
          Send the exact amount to the number above, then tap Continue to enter your transaction ID.
        </p>
      </div>

      <form method="post" action="upsell1.php">
        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
        <button type="submit" class="btn btn-large btn-block" style="margin-top:16px;">
          Continue to Payment →
        </button>
      </form>
<?php endif; ?>

      <div class="trust-badges">
        <span><?= svg_icon('lock') ?> Secure</span>
        <span><?= svg_icon('lightning') ?> Instant</span>
        <span><?= svg_icon('check-circle') ?> Verified</span>
      </div>
    </div>

    <div class="card" style="margin-top:20px;">
      <h3 style="font-size:0.95rem;margin-bottom:8px;">Need Help?</h3>
      <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.6;">
        Contact <a href="mailto:<?= clean(setting('contact_email')) ?>" style="color:var(--coral);font-weight:600;"><?= clean(setting('contact_email')) ?></a>
        or call <a href="tel:<?= clean(setting('momo_account_number')) ?>" style="color:var(--coral);font-weight:600;"><?= clean(setting('momo_account_number')) ?></a>.
      </p>
    </div>
  </div>

</div>

<!-- ============================================= -->
<!-- PREVIEW MODAL                                  -->
<!-- ============================================= -->
<div class="preview-modal" id="previewModal" aria-hidden="true">
  <div class="preview-modal-overlay" data-preview-close></div>
  <div class="preview-modal-panel">
    <div class="preview-modal-head">
      <div>
        <strong class="preview-modal-title">Preview</strong>
        <span class="preview-modal-sub">This is a free sample</span>
      </div>
      <button type="button" class="preview-modal-close" data-preview-close aria-label="Close preview">✕</button>
    </div>

    <div class="preview-modal-body">
      <iframe id="previewIframe" src="" frameborder="0" allow="autoplay"></iframe>
    </div>

    <div class="preview-modal-foot">
      <div>
        <strong>Like what you see?</strong>
        <span>Get the full guide now.</span>
      </div>
      <button type="button" class="btn btn-large" data-preview-close>
        Continue to Payment →
      </button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>