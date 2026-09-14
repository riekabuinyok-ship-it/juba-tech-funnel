<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();

$customer = current_customer();
$bookId = (int)($_GET['book_id'] ?? 0);
$orderId = (int)($_GET['order_id'] ?? 0);
$errors = [];

// Verify the customer actually purchased and downloaded this book
$stmt = $pdo->prepare("
    SELECT o.id FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.id = ? AND o.customer_id = ? AND oi.book_id = ? AND o.status = 'confirmed'
");
$stmt->execute([$orderId, $customer['id'], $bookId]);
if (!$stmt->fetch()) redirect('account.php');

// Check if already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE book_id = ? AND customer_id = ?");
$stmt->execute([$bookId, $customer['id']]);
if ($stmt->fetch()) redirect('account.php');

// Get book
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) $errors[] = 'Please choose a rating from 1 to 5 stars.';
    if (strlen($comment) < 10) $errors[] = 'Please write at least 10 characters so other students can benefit.';
    if (strlen($comment) > 1500) $errors[] = 'Comment is too long. Please keep it under 1500 characters.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO reviews (book_id, customer_id, rating, title, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$bookId, $customer['id'], $rating, $title, $comment]);

        // Mark any review prompt for this book as reviewed
        $stmt = $pdo->prepare("UPDATE review_prompts SET status = 'reviewed' WHERE customer_id = ? AND book_id = ?");
        $stmt->execute([$customer['id'], $bookId]);

        flash('success', 'Your review has been submitted for approval. Thank you!', 'Review received');
        redirect('account.php');
    }
}

$pageTitle = 'Write a Review';
include __DIR__ . '/includes/header.php';
?>

<div class="review-form-wrap">
  <div class="card">
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:6px;">Rate This Guide</h1>
    <p style="color:var(--text-muted);margin-bottom:24px;">
      Your honest feedback helps other South Sudan students choose the right study guides.
    </p>

    <div class="review-book-info">
      <div class="review-book-cover">
        <?php if (!empty($book['cover_image'])): ?>
          <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($book['cover_image']) ?>" alt="">
        <?php else: ?>
          <?= svg_icon('open-book') ?>
        <?php endif; ?>
      </div>
      <div>
        <strong><?= clean($book['title']) ?></strong>
        <small>Order #<?= $orderId ?></small>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= clean($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="review-form">
      <div class="form-group">
        <label>Your Rating</label>
        <div class="star-picker">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
            <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </label>
          <?php endfor; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Review Title (optional)</label>
        <input type="text" name="title" maxlength="150"
               placeholder="e.g. Very helpful for S3 exams"
               value="<?= clean($_POST['title'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Your Review</label>
        <textarea name="comment" rows="6" required minlength="10" maxlength="1500"
                  placeholder="Tell other students what you think about this guide. Was it helpful? Clear? Well-organized?"><?= clean($_POST['comment'] ?? '') ?></textarea>
        <small style="color:var(--text-muted);font-size:0.78rem;">
          Your review will appear after admin approval.
        </small>
      </div>

      <button type="submit" class="btn btn-large btn-block">Submit Review</button>
      <p style="text-align:center;margin-top:14px;">
        <a href="account.php" style="color:var(--text-muted);font-size:0.85rem;">Cancel</a>
      </p>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>