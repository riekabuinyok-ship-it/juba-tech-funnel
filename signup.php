<?php
require_once __DIR__ . '/includes/functions.php';

// NEW: If already logged in, redirect away from signup
if (is_logged_in()) {
    redirect('account.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $classLevel = trim($_POST['class_level'] ?? '');
    $level = $_POST['level_of_study'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($location === '') $errors[] = 'Location is required.';
    if ($classLevel === '') $errors[] = 'Class is required.';
    if (!in_array($level, ['primary','secondary'])) $errors[] = 'Level is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered. Please log in instead.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, password, location, class_level, level_of_study) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $location, $classLevel, $level]);
            $_SESSION['customer_id'] = $pdo->lastInsertId('customers_id_seq');
            redirect('subjects.php?level=' . $level);
        }
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-layout">

  <!-- LEFT: Form -->
  <div class="card auth-form-card">
    <h1 class="auth-title">Create Your Account</h1>
    <p class="auth-sub">Sign up to unlock all study guides. It only takes a minute.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
          <div><?= clean($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post">
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
        <label>Password</label>
        <input type="password" name="password" required placeholder="At least 6 characters">
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
            <option value="primary" <?= (($_POST['level_of_study'] ?? '') === 'primary') ? 'selected' : '' ?>>Primary</option>
            <option value="secondary" <?= (($_POST['level_of_study'] ?? '') === 'secondary') ? 'selected' : '' ?>>Secondary</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-large btn-block" style="margin-top:8px;">
        Sign Up →
      </button>

      <p class="auth-footer">
        Already have an account?
        <a href="login.php">Log in here</a>
      </p>
    </form>
  </div>

  <!-- RIGHT: Benefits sidebar -->
  <aside class="auth-side">

    <div class="side-card side-benefits">
      <h3>Why Create an Account?</h3>
      <ul class="side-list">
        <li><span><?= svg_icon('download') ?></span> Instant access to your downloads</li>
        <li><span><?= svg_icon('receipt') ?></span> Track all your orders in one place</li>
        <li><span><?= svg_icon('lock') ?></span> Unlock new guides instantly after payment</li>
        <li><span><?= svg_icon('gift') ?></span> Get discounts on future purchases</li>
      </ul>
    </div>

    <div class="side-card side-testimonial">
      <p class="quote">
        "Signing up was quick, and I got my Geography guide the same day. Highly recommend!"
      </p>
      <div class="quote-author">
        <div class="avatar-sm">M</div>
        <div>
          <strong>Mary A.</strong>
          <small>Student, Juba</small>
        </div>
      </div>
    </div>

    <div class="trust-strip">
      <span><?= svg_icon('lock') ?> Secure</span>
      <span><?= svg_icon('phone') ?> MoMo</span>
      <span><?= svg_icon('check') ?> Verified</span>
    </div>

  </aside>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>