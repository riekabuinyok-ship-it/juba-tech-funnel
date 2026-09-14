<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Products';

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY level, name")->fetchAll();
$books = $pdo->query("
  SELECT b.*, s.name AS subject_name, s.level AS subject_level
  FROM books b
  JOIN subjects s ON s.id = b.subject_id
  ORDER BY s.level, s.name, b.grade
")->fetchAll();

$primarySubjects = array_filter($subjects, fn($s) => $s['level'] === 'primary');
$secondarySubjects = array_filter($subjects, fn($s) => $s['level'] === 'secondary');

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <div>
    <p class="muted">Manage all subjects and books for Primary and Secondary levels.</p>
  </div>
  <a href="product-edit.php" class="btn">+ Add New Book</a>
</div>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
  <div class="alert alert-success">Book deleted successfully.</div>
<?php elseif (isset($_GET['deleted']) && $_GET['deleted'] === 'error'): ?>
  <div class="alert alert-error">Could not delete this book. It may still have linked data.</div>
<?php endif; ?>

<section class="admin-section">
  <div class="section-head">
    <h2>Subjects</h2>
    <span class="count-pill"><?= count($subjects) ?> total</span>
  </div>

  <h3 class="subhead">Primary</h3>
  <div class="subject-grid">
    <?php foreach ($primarySubjects as $s): ?>
      <div class="subject-card primary">
        <span class="subject-emoji"><?= svg_icon('backpack') ?></span>
        <div>
          <strong><?= clean($s['name']) ?></strong>
          <small>Primary</small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h3 class="subhead">Secondary</h3>
  <div class="subject-grid">
    <?php foreach ($secondarySubjects as $s): ?>
      <div class="subject-card secondary">
        <span class="subject-emoji"><?= svg_icon('graduation-cap') ?></span>
        <div>
          <strong><?= clean($s['name']) ?></strong>
          <small>Secondary</small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="admin-section">
  <div class="section-head">
    <h2>Books</h2>
    <span class="count-pill"><?= count($books) ?> books</span>
  </div>

  <div class="books-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Cover</th>
          <th>Title</th>
          <th>Subject</th>
          <th>Level</th>
          <th>Grade</th>
          <th>Type</th>
          <th>Price</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($books as $b): ?>
          <tr>
            <td>
              <div class="mini-cover">
                <?php if (!empty($b['cover_image'])): ?>
                  <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($b['cover_image']) ?>" alt="">
                <?php else: ?>
                  <span><?= svg_icon('open-book') ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong><?= clean($b['title']) ?></strong>
              <small class="muted">#<?= $b['id'] ?></small>
            </td>
            <td><?= clean($b['subject_name']) ?></td>
            <td>
              <span class="badge badge-<?= $b['subject_level'] ?>">
                <?= ucfirst($b['subject_level']) ?>
              </span>
            </td>
            <td><?= clean($b['grade'] ?: '—') ?></td>
            <td>
              <span class="badge badge-<?= $b['type'] === 'bundle' ? 'bundle' : 'single' ?>">
                <?= $b['type'] === 'bundle' ? 'Bundle' : 'Single' ?>
              </span>
            </td>
            <td><strong><?= money($b['price']) ?></strong></td>
            <td class="row-actions">
              <a href="product-edit.php?id=<?= $b['id'] ?>" class="btn btn-sm">Edit</a>
              <form method="post" action="delete-product.php" class="inline-form"
                    onsubmit="return confirm('Delete &quot;<?= clean($b['title']) ?>&quot;? This cannot be undone.');">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>