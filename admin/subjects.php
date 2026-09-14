<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $level = $_POST['level'] ?? 'secondary';
    if ($name !== '' && in_array($level, ['primary', 'secondary'], true)) {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE name = ? AND level = ?");
        $exists->execute([$name, $level]);
        if ((int)$exists->fetchColumn() > 0) {
            $message = 'A subject with that name already exists for this level.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, level) VALUES (?, ?)");
            $stmt->execute([$name, $level]);
            $message = 'Subject added successfully.';
        }
    } else {
        $message = 'Name and level are required.';
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $count = $pdo->prepare("SELECT COUNT(*) FROM books WHERE subject_id = ?");
    $count->execute([$id]);
    if ((int)$count->fetchColumn() > 0) {
        $message = 'Cannot delete: this subject still has books. Remove its books first.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Subject deleted.';
    }
}

$subjects = $pdo->query("
  SELECT s.id, s.name, s.level,
         (SELECT COUNT(*) FROM books b WHERE b.subject_id = s.id) AS book_count
  FROM subjects s
  ORDER BY s.level, s.name
")->fetchAll();

$primarySubjects   = array_filter($subjects, fn($s) => $s['level'] === 'primary');
$secondarySubjects = array_filter($subjects, fn($s) => $s['level'] === 'secondary');

$pageTitle = 'Subjects';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">Organize study guides by subject for Primary and Secondary levels.</p>
</div>

<?php if ($message): ?>
  <div class="alert alert-info"><?= clean($message) ?></div>
<?php endif; ?>

<section class="admin-section">
  <div class="section-head">
    <h2>Add Subject</h2>
  </div>
  <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
    <div class="form-group" style="flex:1;min-width:200px;margin:0;">
      <label>Name</label>
      <input name="name" required placeholder="e.g. Physics">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Level</label>
      <select name="level">
        <option value="secondary">Secondary</option>
        <option value="primary">Primary</option>
      </select>
    </div>
    <button class="btn">Add Subject</button>
  </form>
</section>

<section class="admin-section">
  <div class="section-head">
    <h2>Secondary</h2>
    <span class="count-pill"><?= count($secondarySubjects) ?> subjects</span>
  </div>
  <div class="subject-grid">
    <?php foreach ($secondarySubjects as $s): ?>
      <div class="subject-card secondary">
        <span class="subject-emoji">🎓</span>
        <div>
          <strong><?= clean($s['name']) ?></strong>
          <small><?= $s['book_count'] ?> book<?= $s['book_count'] == 1 ? '' : 's' ?></small>
        </div>
        <a href="subjects.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline" style="margin-left:auto;padding:4px 10px;" onclick="return confirm('Delete this subject?')">Delete</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="admin-section">
  <div class="section-head">
    <h2>Primary</h2>
    <span class="count-pill"><?= count($primarySubjects) ?> subjects</span>
  </div>
  <div class="subject-grid">
    <?php foreach ($primarySubjects as $s): ?>
      <div class="subject-card primary">
        <span class="subject-emoji">🎒</span>
        <div>
          <strong><?= clean($s['name']) ?></strong>
          <small><?= $s['book_count'] ?> book<?= $s['book_count'] == 1 ? '' : 's' ?></small>
        </div>
        <a href="subjects.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline" style="margin-left:auto;padding:4px 10px;" onclick="return confirm('Delete this subject?')">Delete</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>