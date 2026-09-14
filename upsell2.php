<?php
require_once __DIR__ . '/includes/functions.php';

$bookId = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
if (!$bookId) redirect('index.php');

// Handle Yes / No
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'yes' && !empty($_POST['bundle_id'])) {
        add_to_cart((int)$_POST['bundle_id']);
        flash('success', 'Added to your order', 'Success');
    }
    redirect('payment.php?book_id=' . $bookId);
}

// Collect subject IDs already in cart
$cart = get_cart();
$excludeSubjects = [];
if (!empty($cart)) {
    $ph = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT DISTINCT subject_id FROM books WHERE id IN ($ph)");
    $stmt->execute($cart);
    $excludeSubjects = array_column($stmt->fetchAll(), 'subject_id');
}

// Find a random bundle from a DIFFERENT subject
$sql = "SELECT b.*, s.name AS subject_name
        FROM books b
        JOIN subjects s ON s.id = b.subject_id
        WHERE b.type = 'bundle' AND b.status = 'active'";
$params = [];
if (!empty($excludeSubjects)) {
    $ph = implode(',', array_fill(0, count($excludeSubjects), '?'));
    $sql .= " AND b.subject_id NOT IN ($ph)";
    $params = $excludeSubjects;
}
$sql .= " ORDER BY RANDOM() LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$randomBundle = $stmt->fetch();

if (!$randomBundle) {
    redirect('payment.php?book_id=' . $bookId);
}

$savings = ($randomBundle['price'] < 28000) ? (28000 - $randomBundle['price']) : 0;

$pageTitle = 'One More Offer';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step done">2. Book</div>
  <div class="step active">3. Special Offer</div>
  <div class="step">4. Payment</div>
</div>

<div class="upsell-card">
  <h2>Add the Complete <?= clean($randomBundle['subject_name']) ?> Bundle</h2>
  <p style="color:var(--text-muted);margin-bottom:6px;">
    Top up your order with the full <?= clean($randomBundle['subject_name']) ?> pack (S1–S4) at a discounted price.
  </p>
  <div class="price-big"><?= money($randomBundle['price']) ?></div>
  <?php if ($savings > 0): ?>
    <div class="save-tag">Save <?= money($savings) ?></div>
  <?php endif; ?>

  <div class="upsell-actions">
    <form method="post" style="display:inline;">
      <input type="hidden" name="book_id" value="<?= $bookId ?>">
      <input type="hidden" name="bundle_id" value="<?= $randomBundle['id'] ?>">
      <button type="submit" name="action" value="yes" class="btn btn-large">Yes, Add to Order</button>
    </form>
    <form method="post" style="display:inline;">
      <input type="hidden" name="book_id" value="<?= $bookId ?>">
      <button type="submit" name="action" value="no" class="btn btn-large btn-outline">No Thanks</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>