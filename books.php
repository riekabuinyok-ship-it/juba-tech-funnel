<?php
require_once __DIR__ . '/includes/functions.php';

$subjectId = (int)($_GET['subject_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subjectId]);
$subject = $stmt->fetch();
if (!$subject) redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM books WHERE subject_id = ? AND status = 'active' ORDER BY grade ASC, title ASC");
$stmt->execute([$subjectId]);
$books = $stmt->fetchAll();

// Subject emoji
function subject_emoji($name) {
    return subject_svg($name);
}

$pageTitle = clean($subject['name']) . ' Books';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step active">2. Choose Book</div>
  <div class="step">3. Payment</div>
  <div class="step">4. Download</div>
</div>

<div class="breadcrumb">
  <a href="index.php">Home</a>
  <span>›</span>
  <a href="subjects.php?level=<?= $subject['level'] ?>"><?= ucfirst($subject['level']) ?> Subjects</a>
  <span>›</span>
  <strong><?= clean($subject['name']) ?></strong>
</div>

<div class="books-head">
  <div class="books-head-left">
    <span class="books-head-emoji"><?= subject_emoji($subject['name']) ?></span>
    <div>
      <h1><?= clean($subject['name']) ?> Books</h1>
      <p class="muted">
        <?= count($books) ?> guide<?= count($books) === 1 ? '' : 's' ?> available
      </p>
    </div>
  </div>
  <a href="subjects.php?level=<?= $subject['level'] ?>" class="btn btn-sm btn-outline">
    ← Back to Subjects
  </a>
</div>

<?php if (empty($books)): ?>
  <div class="card empty-state">
    <div class="empty-emoji"><?= svg_icon('open-book') ?></div>
    <h3>No books available yet</h3>
    <p>Guides for <?= clean($subject['name']) ?> will appear here soon. In the meantime, browse other subjects.</p>
    <a href="subjects.php?level=<?= $subject['level'] ?>" class="btn btn-large">
      Browse Other Subjects
    </a>
  </div>
<?php else: ?>
  <div class="books-grid">
    <?php foreach ($books as $b): ?>
      <div class="book-tile">
        <div class="book-cover">
          <?php if (!empty($b['cover_image'])): ?>
            <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($b['cover_image']) ?>" alt="<?= clean($b['title']) ?>">
          <?php else: ?>
            <span><?= subject_emoji($subject['name']) ?></span>
          <?php endif; ?>
          <span class="book-badge <?= $b['type'] === 'bundle' ? 'badge-bundle' : 'badge-single' ?>">
            <?= $b['type'] === 'bundle' ? 'Bundle' : 'Single' ?>
          </span>
          <?php if (($b['price_type'] ?? 'paid') === 'free'): ?>
            <span class="book-badge badge-free">Free</span>
          <?php endif; ?>
          <?php
            $autoBadge = get_book_badge($b['id']);
            if ($autoBadge): ?>
              <span class="book-badge book-badge-auto <?= $autoBadge['class'] ?>" style="top:44px;">
                <?= $autoBadge['label'] ?>
              </span>
          <?php endif; ?>
        </div>

        <div class="book-info">
          <h3 class="book-title"><?= clean($b['title']) ?></h3>
          <p class="book-meta">
            <?= clean($subject['name']) ?>
            <?php if (!empty($b['grade'])): ?> · <?= clean($b['grade']) ?><?php endif; ?>
          </p>

          <div class="book-footer">
            <?php if (($b['price_type'] ?? 'paid') === 'free'): ?>
              <span class="book-price book-free">FREE</span>
            <?php else: ?>
              <span class="book-price"><?= money($b['price']) ?></span>
            <?php endif; ?>
            <?php
              $rating = get_book_rating($b['id']);
            ?>
            <?php if ($rating['total'] > 0): ?>
              <?= render_stars($rating['average'], $rating['total'], 'sm') ?>
            <?php else: ?>
              <span class="book-rating-empty">New</span>
            <?php endif; ?>
          </div>
        </div>

        <a href="sales.php?book_id=<?= $b['id'] ?>" class="btn btn-block book-cta">
          Download Now
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>