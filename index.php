<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-text">
    <span class="hero-badge">Our Best Sale</span>
    <h1>Download Complete <span>Study Guides</span> for South Sudan Students</h1>
    <p>Primary (P1–P8) and Secondary (S1–S4) study guides. Pay easily with MoMo.</p>
    <div class="actions">
      <?php if (is_logged_in()): ?>
        <a href="subjects.php?level=secondary" class="btn btn-large">Browse Secondary</a>
        <a href="subjects.php?level=primary" class="btn btn-large btn-outline">Browse Primary</a>
      <?php else: ?>
        <a href="lead-capture.php?level=secondary" class="btn btn-large">Secondary Guides</a>
        <a href="lead-capture.php?level=primary" class="btn btn-large btn-outline">Primary Guides</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-illustration"><img src="<?= SITE_URL ?>/assets/images/hero.jpg" alt="South Sudan students studying" style="max-width:100%;height:auto;border-radius:16px;box-shadow:0 12px 28px rgba(0,0,0,0.12);"></div>
</section>

<div style="margin: 20px 0 40px;">
  <?= render_trust_strip() ?>
</div>

<div class="section-title">
  <h2>Categories</h2>
  <a href="subjects.php?level=secondary">View All →</a>
</div>
<div class="categories">
  <?php foreach ($subjects as $s): ?>
    <a href="subjects.php?level=<?= $s['level'] ?>" class="category-chip">
      <span class="icon" style="color:var(--coral);"><?= subject_svg($s['name'], 'svg-icon icon-lg') ?></span>
      <span class="name"><?= clean($s['name']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php
// Featured books — different limits for desktop and mobile
// Desktop shows 10, mobile shows 8. We load 10 and hide extras on mobile via CSS.
$stmt = $pdo->query("
    SELECT b.*, s.name AS subject_name, s.level AS subject_level
    FROM books b
    JOIN subjects s ON s.id = b.subject_id
    WHERE b.status = 'active'
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT 10
");
$featuredBooks = $stmt->fetchAll();

// Pick an icon per subject
function featured_emoji($name) {
    return subject_svg($name);
}
?>

<div class="section-title">
  <h2>Featured Books</h2>
  <a href="all-books.php">View All →</a>
</div>

<?php if (empty($featuredBooks)): ?>
  <div class="card" style="text-align:center;padding:50px;">
    <div style="font-size:3rem;margin-bottom:12px;"><?= svg_icon('open-book', 'icon-xl') ?></div>
    <h3 style="font-weight:800;">No books yet</h3>
    <p style="color:var(--text-muted);">Check back soon.</p>
  </div>
<?php else: ?>
  <div class="featured-grid">
    <?php foreach ($featuredBooks as $index => $b): ?>
      <?php
        $rating = get_book_rating($b['id']);
        $autoBadge = get_book_badge($b['id']);
      ?>
<div class="book-tile <?= $index >= 8 ? 'hide-on-mobile' : '' ?>">
  <!-- Clickable cover -->
  <a href="sales.php?book_id=<?= $b['id'] ?>" class="book-cover-link">
    <div class="book-cover">
      <?php if (!empty($b['cover_image'])): ?>
        <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($b['cover_image']) ?>" alt="<?= clean($b['title']) ?>">
      <?php else: ?>
        <span><?= featured_emoji($b['subject_name']) ?></span>
      <?php endif; ?>
      <span class="book-badge <?= $b['type'] === 'bundle' ? 'badge-bundle' : 'badge-single' ?>">
        <?= $b['type'] === 'bundle' ? 'Bundle' : 'Single' ?>
      </span>
      <?php if (($b['price_type'] ?? 'paid') === 'free'): ?>
        <span class="book-badge badge-free">Free</span>
      <?php endif; ?>
      <?php if ($autoBadge): ?>
        <span class="book-badge book-badge-auto <?= $autoBadge['class'] ?>" style="top:44px;">
          <?= $autoBadge['label'] ?>
        </span>
      <?php endif; ?>
    </div>
  </a>

  <div class="book-info">
    <!-- Clickable title -->
    <a href="sales.php?book_id=<?= $b['id'] ?>" class="book-title-link">
      <h3 class="book-title"><?= clean($b['title']) ?></h3>
    </a>
    <p class="book-meta">
      <?= clean($b['subject_name']) ?>
      <?php if (!empty($b['grade'])): ?> · <?= clean($b['grade']) ?><?php endif; ?>
    </p>

    <div class="book-footer">
      <?php if (($b['price_type'] ?? 'paid') === 'free'): ?>
        <span class="book-price book-free">FREE</span>
      <?php else: ?>
        <span class="book-price"><?= money($b['price']) ?></span>
      <?php endif; ?>
      <?php if ($rating['total'] > 0): ?>
        <?= render_stars($rating['average'], null, 'sm') ?>
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

<?php
$stmt = $pdo->query("
    SELECT b.*, s.name AS subject_name, s.level AS subject_level
    FROM books b
    JOIN subjects s ON s.id = b.subject_id
    WHERE b.status = 'active' AND b.price_type = 'free'
    ORDER BY b.created_at DESC
    LIMIT 4
");
$freeBooks = $stmt->fetchAll();
?>

<?php if (!empty($freeBooks)): ?>
  <div class="section-title">
    <h2>Free Study Guides</h2>
    <a href="all-books.php?type=free">View All →</a>
  </div>

  <div class="featured-grid">
    <?php foreach ($freeBooks as $b): ?>
      <?php
        $rating = get_book_rating($b['id']);
        $autoBadge = get_book_badge($b['id']);
      ?>
      <div class="book-tile">
        <a href="sales.php?book_id=<?= $b['id'] ?>" class="book-cover-link">
          <div class="book-cover">
            <?php if (!empty($b['cover_image'])): ?>
              <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($b['cover_image']) ?>" alt="<?= clean($b['title']) ?>">
            <?php else: ?>
              <span><?= featured_emoji($b['subject_name']) ?></span>
            <?php endif; ?>
            <span class="book-badge badge-free">Free</span>
          </div>
        </a>

        <div class="book-info">
          <a href="sales.php?book_id=<?= $b['id'] ?>" class="book-title-link">
            <h3 class="book-title"><?= clean($b['title']) ?></h3>
          </a>
          <p class="book-meta"><?= clean($b['subject_name']) ?> · <?= ucfirst($b['subject_level']) ?></p>

          <div class="book-footer">
            <span class="book-price book-free">FREE</span>
            <?php if ($rating['total'] > 0): ?>
              <?= render_stars($rating['average'], null, 'sm') ?>
            <?php else: ?>
              <span class="book-rating-empty">New</span>
            <?php endif; ?>
          </div>
        </div>

        <a href="sales.php?book_id=<?= $b['id'] ?>" class="btn btn-block book-cta btn-free">
          Get for Free
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
$stmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order, id DESC LIMIT 3");
$testimonials = $stmt->fetchAll();
?>
<?php if (!empty($testimonials)): ?>
  <section style="margin: 50px 0;">
    <h2 style="text-align:center;font-size:1.5rem;font-weight:800;margin-bottom:24px;">Loved by South Sudan Students</h2>
    <div class="testimonial-carousel">
      <?php foreach ($testimonials as $t): ?>
        <div class="testimonial-card">
          <p class="testimonial-quote"><?= clean($t['quote']) ?></p>
          <div class="testimonial-author">
            <div class="testimonial-avatar"><?= strtoupper(substr($t['author_name'], 0, 1)) ?></div>
            <div>
              <strong><?= clean($t['author_name']) ?></strong>
              <small>
                <?= clean($t['author_class']) ?>
                · <?= clean($t['author_location']) ?>
              </small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="promo-banner">
  <div>
    <h2>Get 10% Off Your Order!</h2>
    <p>Enter your email and receive a 10% discount on your next order.</p>
    <a href="signup.php" class="btn" style="background:white;color:var(--green);">Sign Up Now</a>
  </div>
  <div class="promo-illustration"><img src="<?= SITE_URL ?>/assets/images/promo.jpg" alt="Discount" style="width:100%;max-width:280px;height:auto;border-radius:12px;object-fit:cover;"></div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>