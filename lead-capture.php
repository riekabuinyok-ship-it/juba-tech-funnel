<?php
require_once __DIR__ . '/includes/functions.php';

// If already logged in, skip this page
if (is_logged_in()) {
    $next = $_GET['next'] ?? '';
    if ($next) redirect($next);
    $level = $_GET['level'] ?? 'secondary';
    redirect('subjects.php?level=' . $level);
}

$level = $_GET['level'] ?? 'secondary';
$next = $_GET['next'] ?? '';   // NEW
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $classLevel = trim($_POST['class_level'] ?? '');
    $next = $_POST['next'] ?? $_GET['next'] ?? '';   // <-- ADD THIS LINE
    $levelOfStudy = $_POST['level_of_study'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($location === '') $errors[] = 'Location is required.';
    if ($classLevel === '') $errors[] = 'Class is required.';
    if (!in_array($levelOfStudy, ['primary','secondary'])) $errors[] = 'Level is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, password FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if (password_verify($password, $existing['password'])) {
                $_SESSION['customer_id'] = $existing['id'];
                $_SESSION['lead'] = [
                    'name' => $name, 'email' => $email, 'location' => $location,
                    'class_level' => $classLevel, 'level_of_study' => $levelOfStudy
                ];
                // NEW: honor the next parameter
                if ($next) redirect($next);
                redirect('subjects.php?level=' . $levelOfStudy);
            } else {
                $errors[] = 'This email is already registered. Please enter the correct password, or log in instead.';
            }
        } else {
            $_SESSION['lead'] = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'location' => $location,
                'class_level' => $classLevel,
                'level_of_study' => $levelOfStudy
            ];
            // NEW: honor the next parameter
            if ($next) redirect($next);
            redirect('subjects.php?level=' . $levelOfStudy);
        }
    }
}

$pageTitle = 'Your Details';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step active">1. Your Details</div>
  <div class="step">2. Choose Book</div>
  <div class="step">3. Payment</div>
  <div class="step">4. Download</div>
</div>

<div class="lead-layout">

  <!-- LEFT: Form -->
  <div class="card lead-form-card">
    <h1 style="font-size:1.7rem;font-weight:800;letter-spacing:-0.5px;margin-bottom:6px;">
      Tell Us About Yourself
    </h1>
    <p style="color:var(--text-muted);margin-bottom:24px;">
      Create your account in one step. You will use this email and password to log in later.
    </p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
          <div><?= clean($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <?php if ($next): ?>
        <input type="hidden" name="next" value="<?= clean($next) ?>">
      <?php endif; ?>
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" required placeholder="e.g. John Deng"
               value="<?= clean($_POST['name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required placeholder="you@example.com"
               value="<?= clean($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Create a Password</label>
        <input type="password" name="password" required placeholder="At least 6 characters"
               minlength="6">
        <small style="color:var(--text-muted);font-size:0.8rem;display:block;margin-top:6px;">
          You will use this to log in and access your downloads.
        </small>
      </div>

      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" required placeholder="e.g. Juba"
               value="<?= clean($_POST['location'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Class</label>
          <select name="class_level" required>
            <option value="">Select class</option>
            <optgroup label="Primary">
              <?php foreach (['P1','P2','P3','P4','P5','P6','P7','P8'] as $c): ?>
                <option value="<?= $c ?>" <?= (($_POST['class_level'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Secondary">
              <?php foreach (['S1','S2','S3','S4'] as $c): ?>
                <option value="<?= $c ?>" <?= (($_POST['class_level'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>

        <div class="form-group">
          <label>Level of Study</label>
          <select name="level_of_study" required>
            <option value="secondary" <?= ($level === 'secondary') ? 'selected' : '' ?>>Secondary</option>
            <option value="primary" <?= ($level === 'primary') ? 'selected' : '' ?>>Primary</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-large btn-block" style="margin-top:8px;">
        Continue to Guides →
      </button>
    </form>
  </div>

  <!-- RIGHT: Trust sidebar -->
  <aside class="lead-side">
    <div class="side-card side-preview">
      <h3>What You Get</h3>
      <ul class="side-list">
        <li><span><?= svg_icon('open-book') ?></span> Curriculum-aligned study guides</li>
        <li><span><?= svg_icon('lightning') ?></span> Instant download after payment</li>
        <li><span><?= svg_icon('discount') ?></span> Only 7,000 SSP per book</li>
        <li><span><?= svg_icon('gift') ?></span> Save more with full bundles</li>
      </ul>
    </div>

    <div class="side-card side-testimonial">
      <p class="quote">
        "These guides helped me pass my S3 exams. The notes are clear and easy to follow."
      </p>
      <div class="quote-author">
        <div class="avatar-sm">A</div>
        <div>
          <strong>Ayen M.</strong>
          <small>Student, Juba</small>
        </div>
      </div>
    </div>

    <div class="side-card side-stats">
      <div class="stat"><strong>1,500+</strong><small>Students served</small></div>
      <div class="stat"><strong>4.9★</strong><small>Average rating</small></div>
    </div>

    <div class="trust-strip">
      <span><?= svg_icon('lock') ?> Secure</span>
      <span><?= svg_icon('phone') ?> MoMo</span>
      <span><?= svg_icon('check') ?> Verified</span>
    </div>
  </aside>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>