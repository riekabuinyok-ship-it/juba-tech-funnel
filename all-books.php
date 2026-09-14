<?php
require_once __DIR__ . '/includes/functions.php';

// ---------- Read filters from URL ----------
$q          = trim($_GET['q'] ?? '');
$subjectId  = (int)($_GET['subject'] ?? 0);
$level      = $_GET['level'] ?? '';
$type       = $_GET['type'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 12;
$offset     = ($page - 1) * $perPage;

// ---------- Build the WHERE clause ----------
$where  = ["b.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = "(b.title ILIKE ? OR b.description ILIKE ? OR s.name ILIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($subjectId > 0) {
    $where[] = "b.subject_id = ?";
    $params[] = $subjectId;
}
if ($level === 'primary' || $level === 'secondary') {
    $where[] = "s.level = ?";
    $params[] = $level;
}
if ($type === 'free') {
    $where[] = "b.price_type = 'free'";
} elseif ($type === 'single' || $type === 'bundle') {
    $where[] = "b.type = ?";
    $where[] = "b.price_type = 'paid'";
    $params[] = $type;
}
$whereSql = implode(' AND ', $where);

// ---------- Sort ----------
$sortMap = [
    'newest'      => 'b.created_at DESC, b.id DESC',
    'oldest'      => 'b.created_at ASC, b.id ASC',
    'price-low'   => 'b.price ASC',
    'price-high'  => 'b.price DESC',
    'title'       => 'b.title ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

// ---------- Count total ----------
$countSql = "SELECT COUNT(*) FROM books b JOIN subjects s ON s.id = b.subject_id WHERE $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// ---------- Fetch the books ----------
$sql = "
    SELECT b.*, s.name AS subject_name, s.level AS subject_level
    FROM books b
    JOIN subjects s ON s.id = b.subject_id
    WHERE $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// ---------- Load filter options ----------
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY level, name")->fetchAll();

function book_emoji($name) {
    return subject_svg($name);
}

$pageTitle = 'All Books';
include __DIR__ . '/includes/header.php';
?>

<div class="all-books-hero">
  <h1>Browse All Study Guides</h1>
  <p>Primary (P1–P8) and Secondary (S1–S4). Find the right guide for your class.</p>
</div>

<div class="all-books-layout">

  <!-- ============================================ -->
  <!-- SIDEBAR FILTERS                              -->
  <!-- ============================================ -->
<!-- Mobile filter toggle button -->
<button type="button" class="filters-toggle" id="filtersToggle" aria-expanded="false">
  <span><?= svg_icon('search') ?> Filters &amp; Sort</span>
  <span class="filters-toggle-count" id="filtersToggleCount" hidden>0</span>
  <span class="filters-toggle-arrow">▾</span>
</button>

<!-- Filters sidebar -->
<aside class="filters-side" id="filtersSide">
  <div class="filters-side-head">
    <strong>Filters</strong>
    <button type="button" class="filters-close" id="filtersClose" aria-label="Close filters">✕</button>
  </div>

  <form method="get" id="filterForm">

    <div class="filter-block">
      <label class="filter-label">Search</label>
      <div class="search-input-wrap">
        <input type="text" name="q" value="<?= clean($q) ?>" placeholder="Search by title, subject...">
        <button type="submit" aria-label="Search"><?= svg_icon('search') ?></button>
      </div>
    </div>

    <div class="filter-block">
      <label class="filter-label">Level</label>
      <div class="filter-chips">
        <a href="?<?= http_build_query(array_merge($_GET, ['level' => '', 'page' => 1])) ?>"
           class="filter-chip <?= $level === '' ? 'active' : '' ?>">All</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['level' => 'primary', 'page' => 1])) ?>"
           class="filter-chip <?= $level === 'primary' ? 'active' : '' ?>">Primary</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['level' => 'secondary', 'page' => 1])) ?>"
           class="filter-chip <?= $level === 'secondary' ? 'active' : '' ?>">Secondary</a>
      </div>
    </div>

    <div class="filter-block">
      <label class="filter-label">Type</label>
      <div class="filter-chips">
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => '', 'page' => 1])) ?>"
           class="filter-chip <?= $type === '' ? 'active' : '' ?>">All</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'single', 'page' => 1])) ?>"
           class="filter-chip <?= $type === 'single' ? 'active' : '' ?>">Single</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'bundle', 'page' => 1])) ?>"
           class="filter-chip <?= $type === 'bundle' ? 'active' : '' ?>">Bundle</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'free', 'page' => 1])) ?>"
           class="filter-chip <?= $type === 'free' ? 'active' : '' ?>">Free</a>
      </div>
    </div>

    <div class="filter-block">
      <label class="filter-label">Subject</label>
      <div class="subject-scroll">
        <a href="?<?= http_build_query(array_merge($_GET, ['subject' => 0, 'page' => 1])) ?>"
           class="subject-filter <?= $subjectId === 0 ? 'active' : '' ?>">
          <span><?= svg_icon('open-book') ?></span> All Subjects
        </a>
        <?php foreach ($subjects as $s): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['subject' => $s['id'], 'page' => 1])) ?>"
             class="subject-filter <?= $subjectId === (int)$s['id'] ? 'active' : '' ?>">
            <span><?= book_emoji($s['name']) ?></span> <?= clean($s['name']) ?>
            <small><?= ucfirst($s['level']) ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="filter-block">
      <label class="filter-label">Sort By</label>
      <select name="sort" class="filter-select" onchange="document.getElementById('filterForm').submit();">
        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest first</option>
        <option value="oldest"     <?= $sort === 'oldest'     ? 'selected' : '' ?>>Oldest first</option>
        <option value="price-low"  <?= $sort === 'price-low'  ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: high to low</option>
        <option value="title"      <?= $sort === 'title'      ? 'selected' : '' ?>>Title A–Z</option>
      </select>
    </div>

    <?php if ($q || $subjectId || $level || $type): ?>
      <a href="all-books.php" class="clear-filters">✕ Clear all filters</a>
    <?php endif; ?>

  </form>
</aside>

<!-- Overlay for mobile drawer -->
<div class="filters-overlay" id="filtersOverlay" hidden></div>

  <!-- ============================================ -->
  <!-- RESULTS                                       -->
  <!-- ============================================ -->
  <div class="results-side">

    <div class="results-head">
      <div>
        <strong><?= number_format($total) ?></strong> guide<?= $total === 1 ? '' : 's' ?> found
        <?php if ($q): ?>
          for "<em><?= clean($q) ?></em>"
        <?php endif; ?>
      </div>
      <div class="results-view-toggle">
        <button type="button" class="view-btn active" data-view="grid" aria-label="Grid view">▦</button>
        <button type="button" class="view-btn" data-view="list" aria-label="List view">☰</button>
      </div>
    </div>

    <?php if (empty($books)): ?>
      <div class="card" style="text-align:center;padding:60px 30px;">
        <div style="font-size:4rem;margin-bottom:12px;"><?= svg_icon('search') ?></div>
        <h3 style="font-weight:800;margin-bottom:8px;">No results found</h3>
        <p style="color:var(--text-muted);margin-bottom:22px;">Try adjusting your search or filters.</p>
        <a href="all-books.php" class="btn">Clear Filters</a>
      </div>
    <?php else: ?>
      <div class="all-books-grid" id="resultsGrid">
        <?php foreach ($books as $b): ?>
          <?php
            $rating = get_book_rating($b['id']);
            $autoBadge = get_book_badge($b['id']);
          ?>
          <div class="book-tile">
            <!-- Clickable cover -->
            <a href="sales.php?book_id=<?= $b['id'] ?>" class="book-cover-link">
              <div class="book-cover">
                <?php if (!empty($b['cover_image'])): ?>
                  <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($b['cover_image']) ?>" alt="<?= clean($b['title']) ?>">
                <?php else: ?>
                  <span><?= book_emoji($b['subject_name']) ?></span>
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
                · <?= ucfirst($b['subject_level']) ?>
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

      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">← Prev</a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 1): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                 class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php elseif (abs($i - $page) == 2): ?>
              <span class="page-ellipsis">…</span>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>