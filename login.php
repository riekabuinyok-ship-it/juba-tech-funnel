<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password'])) {
            $_SESSION['customer_id'] = $customer['id'];
            $redirect = $_GET['redirect'] ?? 'account.php';
            redirect($redirect);
        } else {
            flash('error', 'Invalid email or password. Please try again.', 'Login failed');
            $back = 'login.php' . (!empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '');
            redirect($back);
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-layout">

  <!-- LEFT: Form -->
  <div class="card auth-form-card">
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-sub">Log in to access your study guides and downloads.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
          <div><?= clean($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required placeholder="you@example.com"
               value="<?= clean($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Your password">
      </div>

      <div class="form-forgot">
        <a href="forgot-password.php">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-large btn-block" style="margin-top:8px;">
        Login →
      </button>

      <p class="auth-footer">
        Don't have an account?
        <a href="signup.php">Create one here</a>
      </p>
    </form>
  </div>

  <!-- RIGHT: Sidebar -->
  <aside class="auth-side">

    <div class="side-card side-benefits">
      <h3>Why Log In?</h3>
      <ul class="side-list">
        <li><span><?= svg_icon('download') ?></span> Access all your purchased guides</li>
        <li><span><?= svg_icon('receipt') ?></span> View your full order history</li>
        <li><span><?= svg_icon('lock') ?></span> Re-download files anytime</li>
        <li><span><?= svg_icon('gift') ?></span> Get notified of new releases</li>
      </ul>
    </div>

    <div class="side-card side-testimonial">
      <p class="quote">
        "Having all my study guides in one account made revision so much easier."
      </p>
      <div class="quote-author">
        <div class="avatar-sm">D</div>
        <div>
          <strong>David O.</strong>
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