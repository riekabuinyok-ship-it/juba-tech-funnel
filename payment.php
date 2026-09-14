<?php
require_once __DIR__ . '/includes/functions.php';

$bookId = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
if (!$bookId) redirect('index.php');

if (!is_logged_in() && empty($_SESSION['lead'])) {
    // Send them to lead-capture but remember to come back here
    redirect('lead-capture.php?next=' . urlencode('payment.php?book_id=' . $bookId));
}

$accountWasJustCreated = false;

if (!is_logged_in()) {
    $lead = $_SESSION['lead'];

    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$lead['email']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $_SESSION['customer_id'] = $existing['id'];
    } else {
        $hash = password_hash($lead['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, password, location, class_level, level_of_study) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $lead['name'],
            $lead['email'],
            $hash,
            $lead['location'],
            $lead['class_level'],
            $lead['level_of_study']
        ]);
        $_SESSION['customer_id'] = $pdo->lastInsertId();
        $accountWasJustCreated = true;
        $_SESSION['account_just_created'] = true;
    }
}

// Handle remove item action
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    remove_from_cart($removeId);
    redirect('payment.php?book_id=' . $bookId);
}

// Handle clear cart
if (isset($_GET['clear'])) {
    clear_cart();
    redirect('index.php');
}

$cart = get_cart();
if (empty($cart)) {
    flash('warning', 'Your cart is empty. Please choose a book first.', 'Cart empty');
    redirect('index.php');
}

// Also verify the items actually exist in the DB
$placeholders = implode(',', array_fill(0, count($cart), '?'));
$stmt = $pdo->prepare("SELECT * FROM books WHERE id IN ($placeholders) AND status = 'active'");
$stmt->execute($cart);
$items = $stmt->fetchAll();

if (empty($items)) {
    clear_cart();
    flash('warning', 'The books in your cart are no longer available.', 'Cart cleared');
    redirect('index.php');
}
$total = array_sum(array_column($items, 'price'));

// Auto-apply a pending coupon from the exit-intent modal
if (!get_applied_coupon() && !empty($_SESSION['pending_coupon'])) {
    $result = apply_coupon($_SESSION['pending_coupon'], $total);
    if ($result['ok']) {
        set_applied_coupon([
            'id' => $result['coupon']['id'],
            'code' => $result['coupon']['code'],
            'discount' => $result['discount'],
            'type' => $result['coupon']['discount_type'],
            'value' => $result['coupon']['discount_value'],
        ]);
    }
    unset($_SESSION['pending_coupon']);
}

// Handle coupon removal
if (isset($_GET['remove_coupon'])) {
    clear_applied_coupon();
    redirect('payment.php?book_id=' . $bookId);
}

$errors = [];

// Handle coupon submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $result = apply_coupon($_POST['coupon_code'] ?? '', $total);
    if ($result['ok']) {
        set_applied_coupon([
            'id' => $result['coupon']['id'],
            'code' => $result['coupon']['code'],
            'discount' => $result['discount'],
            'type' => $result['coupon']['discount_type'],
            'value' => $result['coupon']['discount_value'],
        ]);
        redirect('payment.php?book_id=' . $bookId);
    } else {
        $errors[] = $result['error'];
    }
}

$appliedCoupon = get_applied_coupon();
$couponDiscount = $appliedCoupon ? (float)$appliedCoupon['discount'] : 0;
$payTotal = max(0, $total - $couponDiscount);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'])) {
    $transactionId = trim($_POST['transaction_id']);

    // NEW: Basic validation
    if ($transactionId === '') {
        flash('error', 'Transaction ID is required.', 'Invalid');
    } elseif (strlen($transactionId) < 6) {
        flash('error', 'Please check your Transaction ID. It looks too short.', 'Invalid');
    } elseif (!preg_match('/^[A-Za-z0-9\-\.]+$/', $transactionId)) {
        flash('error', 'Transaction ID contains invalid characters.', 'Invalid');
    } else {
        // NEW: Check for duplicate transaction ID before doing anything
        $stmt = $pdo->prepare("SELECT id, order_id FROM payments WHERE transaction_id = ? LIMIT 1");
        $stmt->execute([$transactionId]);
        $existing = $stmt->fetch();

        if ($existing) {
            flash('error', 'This MoMo Transaction ID has already been used. Each payment can only be used once. Please check your SMS and enter a new one.', 'Invalid');
        } else {
            $pdo->beginTransaction();
            try {
$coupon = get_applied_coupon();
$discount = $coupon ? (float)$coupon['discount'] : 0;
$finalTotal = max(0, $total - $discount);
$couponCode = $coupon ? $coupon['code'] : null;

                $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, subtotal, discount_amount, coupon_code, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['customer_id'], $finalTotal, $total, $discount, $couponCode]);
                $orderId = $pdo->lastInsertId();

                // Insert order items
                foreach ($items as $item) {
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, price) VALUES (?, ?, ?)");
                    $stmt->execute([$orderId, $item['id'], $item['price']]);
                }

                // Insert payment (unique index will block duplicates as a safety net)
                $stmt = $pdo->prepare("INSERT INTO payments (order_id, momo_account_name, momo_account_number, transaction_id, amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([
                    $orderId,
                    setting('momo_account_name'),
                    setting('momo_account_number'),
                    $transactionId,
                    $finalTotal
                ]);

                if ($coupon) {
                    increment_coupon_usage($coupon['id']);
                }

                $pdo->commit();

                // Notify all admins of the new order
                notify_all_admins(
                    'new_order',
                    'New Order Received',
                    'Order #' . $orderId . ' for ' . money($finalTotal) . ' is waiting for payment confirmation.',
                    'order-view.php?id=' . $orderId
                );

                clear_applied_coupon();
                unset($_SESSION['cart']);
                flash('warning', 'Your payment is still under review.', 'Please wait');
                redirect('thank-you.php?order_id=' . $orderId);
            } catch (PDOException $e) {
                $pdo->rollBack();
                // NEW: Catch unique constraint violation as final defence
                if (in_array($e->getCode(), ['23505', '23000'])) {
                    $errors[] = 'This MoMo Transaction ID has already been used. Each payment can only be used once.';
                } else {
                    $errors[] = 'Something went wrong. Please try again.';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Payment';
include __DIR__ . '/includes/header.php';
?>

<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step done">2. Book</div>
  <div class="step active">3. Payment</div>
  <div class="step">4. Download</div>
</div>

<h1 style="font-size:1.7rem;font-weight:800;letter-spacing:-0.5px;margin:8px 0 24px;">Final Payment</h1>

<div class="payment-layout">

  <!-- LEFT: Order Summary -->
  <div>
    <div class="card">
      <div class="card-head-row">
        <h2>Order Summary</h2>
        <?php if (count($items) > 1): ?>
          <a href="payment.php?book_id=<?= $bookId ?>&clear=1"
             class="clear-cart-link"
             onclick="return confirm('Remove all items from your cart?');">
            Clear all
          </a>
        <?php endif; ?>
      </div>

      <ul class="order-summary-list">
        <?php foreach ($items as $item): ?>
          <li>
            <div class="summary-cover">
              <?= $item['type'] === 'bundle' ? svg_icon('package') : svg_icon('open-book') ?>
            </div>
            <div class="summary-info">
              <span class="summary-title"><?= clean($item['title']) ?></span>
              <small><?= $item['type'] === 'bundle' ? 'Bundle' : 'Single Book' ?></small>
            </div>
            <div class="summary-actions">
              <strong class="summary-price"><?= money($item['price']) ?></strong>
              <a href="payment.php?book_id=<?= $bookId ?>&remove=<?= $item['id'] ?>"
                 class="remove-btn"
                 title="Remove from cart"
                 onclick="return confirm('Remove &quot;<?= clean($item['title']) ?>&quot; from your order?');">
                ✕
              </a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="order-summary-total">
        <span>Subtotal</span>
        <strong style="color:var(--text);"><?= money($total) ?></strong>
      </div>

      <?php
      $coupon = get_applied_coupon();
      $discount = $coupon ? (float)$coupon['discount'] : 0;
      $finalTotal = max(0, $total - $discount);
      ?>

      <?php if ($discount > 0): ?>
        <div class="order-discount-row">
          <span>Discount (<?= clean($coupon['code']) ?>)</span>
          <strong>− <?= money($discount) ?></strong>
        </div>
      <?php endif; ?>

      <div class="order-summary-total order-total-final">
        <span>Total</span>
        <strong><?= money($finalTotal) ?></strong>
      </div>

      <div class="coupon-box">
        <?php if ($discount > 0): ?>
          <div class="coupon-applied">
            <span><?= svg_icon('party') ?> Coupon <strong><?= clean($coupon['code']) ?></strong> applied</span>
            <a href="payment.php?book_id=<?= $bookId ?>&remove_coupon=1">Remove</a>
          </div>
        <?php else: ?>
          <form method="post" class="coupon-form">
            <input type="text" name="coupon_code" placeholder="Enter coupon code" value="<?= clean($_POST['coupon_code'] ?? '') ?>">
            <button type="submit" name="apply_coupon" value="1" class="btn btn-sm">Apply</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <h3 style="font-size:1rem;margin-bottom:10px;"><?= svg_icon('lock') ?> Secure Checkout</h3>
      <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.6;margin:0;">
        Your payment is verified manually by our admin team. Once confirmed, your download links unlock automatically.
      </p>
    </div>
  </div>

  <!-- RIGHT: Payment Panel -->
  <div>
    <div class="card payment-panel">
      <h2>Pay with MoMo</h2>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $e): ?>
            <div><?= clean($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="payment-instruction">
        Send <strong><?= money($finalTotal) ?></strong> to:
      </div>

      <div class="payment-box">
        <div class="label">Account Name</div>
        <div class="value"><?= clean(setting('momo_account_name')) ?></div>

        <div class="label">Account Number</div>
        <div class="value mono"><?= clean(setting('momo_account_number')) ?></div>

        <div class="label">Amount to Send</div>
        <div class="value" style="color:var(--coral);font-size:1.4rem;font-weight:800;">
          <?= money($finalTotal) ?>
        </div>
      </div>

      <form method="post">
        <div class="form-group">
          <label>MoMo Transaction ID</label>
          <input type="text" name="transaction_id" required
                 placeholder="e.g. MP240412.1234.A56789"
                 value="<?= clean($_POST['transaction_id'] ?? '') ?>">
          <small>
            Enter the exact transaction ID from your MoMo SMS. Each ID can only be used once.
          </small>
        </div>

        <button type="submit" class="btn btn-block">
          Pay Now →
        </button>
      </form>

      <div class="trust-badges">
        <span><?= svg_icon('lock') ?> Secure</span>
        <span><?= svg_icon('check') ?> Verified</span>
        <span><?= svg_icon('lightning') ?> Fast</span>
      </div>
    </div>

    <div class="card" style="margin-top:20px;">
      <h3 style="font-size:0.95rem;margin-bottom:8px;">Need Help?</h3>
      <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.6;margin:0;">
        Contact <a href="mailto:<?= clean(setting('contact_email')) ?>" style="color:var(--coral);font-weight:600;"><?= clean(setting('contact_email')) ?></a>
        or call <a href="tel:<?= clean(setting('momo_account_number')) ?>" style="color:var(--coral);font-weight:600;"><?= clean(setting('momo_account_number')) ?></a>.
      </p>
    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>