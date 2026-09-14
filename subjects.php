<?php
require_once __DIR__ . '/includes/functions.php';

$level = $_GET['level'] ?? ($_SESSION['lead']['level_of_study'] ?? 'secondary');
if (!in_array($level, ['primary','secondary'])) $level = 'secondary';

$stmt = $pdo->prepare("SELECT * FROM subjects WHERE level = ? ORDER BY name ASC");
$stmt->execute([$level]);
$subjects = $stmt->fetchAll();

// Decide icon by subject keyword
function subject_emoji($name) {
    return subject_svg($name);
}

$pageTitle = ucfirst($level) . ' Subjects';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Your Details</div>
  <div class="step active">2. Choose Book</div>
  <div class="step">3. Payment</div>
  <div class="step">4. Download</div>
</div>

<div class="breadcrumb">
  <a href="index.php">Home</a>
  <span>›</span>
  <strong><?= ucfirst($level) ?> Guides</strong>
</div>

<div class="subjects-head">
  <div>
    <h1><?= ucfirst($level) ?> Subjects</h1>
    <p class="muted">
      <?= count($subjects) ?> subject<?= count($subjects) === 1 ? '' : 's' ?> available. Pick a subject to see its books.
    </p>
  </div>
  <a href="lead-capture.php?level=<?= $level === 'secondary' ? 'primary' : 'secondary' ?>" class="btn btn-sm btn-outline">
    Switch to <?= $level === 'secondary' ? 'Primary' : 'Secondary' ?>
  </a>
</div>

<?php if (empty($subjects)): ?>
  <div class="card empty-state">
    <div class="empty-emoji"><?= svg_icon('open-book') ?></div>
    <h3>No subjects yet</h3>
    <p>Subjects will appear here once the admin adds them.</p>
  </div>
<?php else: ?>
  <div class="subjects-grid">
    <?php foreach ($subjects as $s): ?>
      <a href="books.php?subject_id=<?= $s['id'] ?>" class="subject-tile">
        <span class="subject-tile-emoji"><?= subject_emoji($s['name']) ?></span>
        <span class="subject-tile-name"><?= clean($s['name']) ?></span>
        <span class="subject-tile-cta">View Books →</span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>