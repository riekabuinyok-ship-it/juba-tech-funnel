<?php
require_once __DIR__ . '/includes/functions.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) redirect('index.php');

$customer = current_customer();

// Was this account created just now? If so, show the credentials block once.
$showCredentials = false;
if (!empty($_SESSION['account_just_created'])) {
    $showCredentials = true;
    unset($_SESSION['account_just_created']); // show only once
}

$pageTitle = 'Thank You';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step done">2. Book</div>
  <div class="step done">3. Payment</div>
  <div class="step active">4. Download</div>
</div>

<div class="thankyou-card">
  <div class="thankyou-icon"><?= svg_icon('party') ?></div>
  <h1>Thank You for Your Order</h1>
  <p class="thankyou-sub">
    Your order number is <strong>#<?= $orderId ?></strong>
  </p>

  <div class="thankyou-status">
    <span class="status-dot"></span>
    Payment under review. Your download links will unlock once the admin confirms your MoMo transaction.
  </div>

  <?php if ($showCredentials && $customer): ?>
    <div class="thankyou-account">
      <h3>Your Account Is Ready</h3>
      <p>You created an account during checkout. Use these details to log in anytime:</p>

      <div class="account-credentials">
        <div>
          <span class="cred-label">Email</span>
          <strong><?= clean($customer['email']) ?></strong>
        </div>
        <div>
          <span class="cred-label">Password</span>
          <strong>••••••••</strong>
          <small>(the one you set at checkout)</small>
        </div>
      </div>

      <p class="hint">
        Save these details. You will need them to download your guides later.
      </p>
    </div>
  <?php endif; ?>

  <div class="thankyou-actions">
    <a href="account.php" class="btn btn-large">Go to My Account</a>
    <a href="index.php" class="btn btn-large btn-outline">Back to Home</a>
  </div>
</div>

<style>
.thankyou-card { max-width: 560px; margin: 40px auto; padding: 40px 32px; background: #fff; border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.06); border: 1px solid var(--border); text-align: center; }
.thankyou-icon { font-size: 4rem; margin-bottom: 12px; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.08)); }
.thankyou-card h1 { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 6px; }
.thankyou-sub { color: var(--text-muted); margin-bottom: 24px; }
.thankyou-sub strong { color: var(--coral); }
.thankyou-status { display: flex; align-items: center; gap: 10px; background: #fff9ea; border: 1px solid #ffe4a1; color: #8a5a00; border-radius: 12px; padding: 14px 18px; font-size: 0.9rem; font-weight: 600; text-align: left; margin-bottom: 24px; }
.status-dot { width: 10px; height: 10px; border-radius: 50%; background: #f4a261; flex-shrink: 0; animation: pulse 1.6s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.85); } }
.thankyou-account { background: linear-gradient(135deg, #f5fbf9, #e5f5ef); border: 1px solid #c9e9dd; border-radius: 14px; padding: 22px; text-align: left; margin-bottom: 24px; }
.thankyou-account h3 { font-size: 1rem; font-weight: 800; color: #1a6b4a; margin-bottom: 6px; }
.thankyou-account p { font-size: 0.88rem; color: var(--text); margin-bottom: 14px; line-height: 1.55; }
.account-credentials { background: #fff; border-radius: 10px; padding: 14px 16px; display: flex; flex-direction: column; gap: 12px; margin-bottom: 12px; }
.account-credentials > div { display: flex; flex-direction: column; gap: 2px; }
.cred-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 800; color: var(--text-muted); }
.account-credentials strong { font-size: 0.95rem; font-weight: 700; color: var(--text); font-family: 'Courier New', monospace; }
.account-credentials small { font-size: 0.75rem; color: var(--text-muted); }
.thankyou-account .hint { font-size: 0.82rem; color: #1a6b4a; font-weight: 600; margin: 0; }
.thankyou-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
