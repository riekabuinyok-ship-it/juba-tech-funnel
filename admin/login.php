<?php
require_once __DIR__ . '/../includes/functions.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        redirect('dashboard.php');
    } else {
        $errors[] = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login · <?= clean(setting('site_name', SITE_NAME)) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="admin-login-body">

  <div class="admin-login-wrap">

    <div class="admin-login-brand">
      <div class="admin-login-logo"><?= svg_icon('lock') ?></div>
      <h1>Admin Panel</h1>
      <p><?= clean(setting('site_name', SITE_NAME)) ?></p>
    </div>

    <form method="post" class="admin-login-card">
      <?php if ($errors): ?>
        <div class="admin-login-error">
          <?php foreach ($errors as $e): ?>
            <div><?= svg_icon('cross') ?> <?= clean($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="admin-login-field">
        <label for="adminEmail">Email Address</label>
        <input type="email" id="adminEmail" name="email" required
               placeholder="admin@example.com"
               autocomplete="username"
               value="<?= clean($_POST['email'] ?? '') ?>">
      </div>

      <div class="admin-login-field">
        <label for="adminPass">Password</label>
        <div class="admin-login-pass">
          <input type="password" id="adminPass" name="password" required
                 placeholder="Enter your password"
                 autocomplete="current-password">
          <button type="button" class="admin-login-eye" id="togglePass" aria-label="Show password">
            <?= svg_icon('eye') ?>
          </button>
        </div>
      </div>

      <button type="submit" class="admin-login-btn">
        Sign In →
      </button>

      <p class="admin-login-note">
        <?= svg_icon('lock') ?> Authorized personnel only. All activity is logged.
      </p>
    </form>

    <p class="admin-login-footer">
      © <?= date('Y') ?> <?= clean(setting('site_name', SITE_NAME)) ?>
    </p>

  </div>

<script>
(function() {
  var btn = document.getElementById('togglePass');
  var input = document.getElementById('adminPass');
  if (!btn || !input) return;
  btn.addEventListener('click', function() {
    if (input.type === 'password') {
      input.type = 'text';
      btn.setAttribute('aria-label', 'Hide password');
    } else {
      input.type = 'password';
      btn.setAttribute('aria-label', 'Show password');
    }
  });
})();
</script>

</body>
</html>
