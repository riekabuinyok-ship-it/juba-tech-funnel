<?php
require_once __DIR__ . '/includes/functions.php';

$bookId = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
if (!$bookId) redirect('index.php');

$stmt = $pdo->prepare("
    SELECT b.*, s.name AS subject_name
    FROM books b
    JOIN subjects s ON s.id = b.subject_id
    WHERE b.id = ?
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();
if (!$book) redirect('index.php');

// Handle Yes / No first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'yes' && !empty($_POST['bundle_id'])) {
        add_to_cart((int)$_POST['bundle_id']);
        flash('success', 'Added to your order', 'Success');
    }
    redirect('upsell2.php?book_id=' . $bookId);
}

// If the user is already buying a bundle, skip this upsell
if ($book['type'] === 'bundle') {
    redirect('upsell2.php?book_id=' . $bookId);
}

// Find the bundle for the SAME subject
$stmt = $pdo->prepare("
    SELECT * FROM books
    WHERE subject_id = ? AND type = 'bundle' AND status = 'active'
    LIMIT 1
");
$stmt->execute([$book['subject_id']]);
$bundle = $stmt->fetch();

// If no bundle exists for this subject, skip this upsell
if (!$bundle) {
    redirect('upsell2.php?book_id=' . $bookId);
}

$savings = ($bundle['price'] < 28000) ? (28000 - $bundle['price']) : 0;

$pageTitle = 'Special Offer';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step done">2. Book</div>
  <div class="step active">3. Special Offer</div>
  <div class="step">4. Payment</div>
</div>

<div class="upsell-card">
  <h2>Upgrade to the Complete <?= clean($book['subject_name']) ?> Bundle</h2>
  <p style="color:var(--text-muted);margin-bottom:6px;">
    Get all four <?= clean($book['subject_name']) ?> books (S1–S4) in one pack
    and save compared to buying them separately.
  </p>
  <div class="price-big"><?= money($bundle['price']) ?></div>
  <?php if ($savings > 0): ?>
    <div class="save-tag">Save <?= money($savings) ?></div>
  <?php endif; ?>

  <div class="upsell-compare">
  <div class="compare-row">
    <span class="compare-label">You currently selected</span>
    <span class="compare-value">
      <?= clean($book['title']) ?>
      <strong><?= money($book['price']) ?></strong>
    </span>
  </div>

  <div class="compare-divider">
    <span>↓</span>
  </div>

  <div class="compare-row highlight">
    <span class="compare-label">You will get</span>
    <span class="compare-value">
      <?= clean($bundle['title']) ?>
      <strong><?= money($bundle['price']) ?></strong>
    </span>
  </div>
</div>

  <div class="upsell-actions">
    <form method="post" style="display:inline;">
      <input type="hidden" name="book_id" value="<?= $bookId ?>">
      <input type="hidden" name="bundle_id" value="<?= $bundle['id'] ?>">
      <button type="submit" name="action" value="yes" class="btn btn-large">Yes, Upgrade</button>
    </form>
    <form method="post" style="display:inline;">
      <input type="hidden" name="book_id" value="<?= $bookId ?>">
      <button type="submit" name="action" value="no" class="btn btn-large btn-outline">Not Now</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>