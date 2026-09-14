<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Reviews';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['review_id'];
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $pdo->prepare("UPDATE reviews SET status='approved', approved_at=NOW() WHERE id=?")->execute([$id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
    }
    redirect('reviews.php');
}

$status = $_GET['status'] ?? 'pending';
$stmt = $pdo->prepare("
    SELECT r.*, c.name AS customer_name, c.email AS customer_email, c.class_level,
           b.title AS book_title
    FROM reviews r
    JOIN customers c ON c.id = r.customer_id
    JOIN books b ON b.id = r.book_id
    WHERE r.status = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$status]);
$reviews = $stmt->fetchAll();

$counts = [];
foreach (['pending','approved','rejected'] as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE status = ?");
    $stmt->execute([$s]);
    $counts[$s] = $stmt->fetchColumn();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">Moderate real reviews from customers. Only approved reviews appear on the site.</p>
</div>

<div class="review-tabs">
  <?php foreach (['pending','approved','rejected'] as $s): ?>
    <a href="reviews.php?status=<?= $s ?>"
       class="review-tab <?= $status === $s ? 'active' : '' ?>">
      <?= ucfirst($s) ?>
      <span class="review-tab-count"><?= $counts[$s] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php if (empty($reviews)): ?>
  <div class="card" style="text-align:center;padding:50px;">
    <div style="font-size:3rem;margin-bottom:12px;"><?= svg_icon('star', 'icon-xl') ?></div>
    <h3 style="font-weight:800;">No <?= $status ?> reviews</h3>
    <p style="color:var(--text-muted);">Reviews will show up here when customers submit them.</p>
  </div>
<?php else: ?>
  <div class="review-admin-list">
    <?php foreach ($reviews as $r): ?>
      <div class="card review-admin-card">
        <div class="review-admin-head">
          <div>
            <strong><?= clean($r['customer_name']) ?></strong>
            <small><?= clean($r['customer_email']) ?> · <?= clean($r['class_level']) ?></small>
          </div>
          <div class="review-admin-rating"><?= render_stars($r['rating'], null, 'sm') ?></div>
        </div>
        <div class="review-admin-book">
          On: <strong><?= clean($r['book_title']) ?></strong>
          · <?= date('M j, Y', strtotime($r['created_at'])) ?>
        </div>
        <?php if (!empty($r['title'])): ?>
          <h4 style="font-size:0.95rem;font-weight:700;margin:12px 0 4px;"><?= clean($r['title']) ?></h4>
        <?php endif; ?>
        <p style="color:var(--text);font-size:0.9rem;line-height:1.6;"><?= nl2br(clean($r['comment'])) ?></p>

        <?php if ($r['status'] === 'pending'): ?>
          <div class="review-admin-actions">
            <form method="post" style="display:inline;">
              <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
              <button name="action" value="approve" class="btn btn-sm btn-green">✓ Approve</button>
            </form>
            <form method="post" style="display:inline;">
              <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
              <button name="action" value="reject" class="btn btn-sm btn-outline">✕ Reject</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete permanently?');">
              <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
              <button name="action" value="delete" class="btn btn-sm" style="background:#6b6b6b;"><?= svg_icon('trash') ?> Delete</button>
            </form>
          </div>
        <?php else: ?>
          <div class="review-admin-actions">
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this review?');">
              <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
              <button name="action" value="delete" class="btn btn-sm" style="background:#6b6b6b;"><?= svg_icon('trash') ?> Delete</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>