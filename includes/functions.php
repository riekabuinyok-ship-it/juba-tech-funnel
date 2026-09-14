<?php
require_once __DIR__ . '/db.php';

function setting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function money($amount) {
    return number_format($amount, 0) . ' ' . CURRENCY;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function clean($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function svg_icon($file, $class = 'svg-icon') {
    $path = __DIR__ . '/../assets/icons/' . $file . '.svg';
    if (!is_file($path)) return '';
    $svg = @file_get_contents($path);
    if ($svg === false) return '';
    $svg = preg_replace('/<\?xml[^>]*\?>\s*/i', '', $svg);
    $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $svg);
    $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg);
    $svg = preg_replace('/\bwidth="[^"]*"\s*/i', '', $svg);
    $svg = preg_replace('/\bheight="[^"]*"\s*/i', '', $svg);
    $svg = str_replace('<svg', '<svg class="' . $class . '" focusable="false" aria-hidden="true"', $svg);
    $svg = preg_replace('/\bfill="[^"]*"/i', 'fill="currentColor"', $svg);
    $svg = preg_replace('/\bstroke="#?[0-9a-fA-F]{3,6}"/i', 'stroke="currentColor"', $svg);
    return $svg;
}

function subject_svg($name, $class = 'svg-icon') {
    $map = [
        'geography' => 'globe', 'english' => 'open-book', 'cre' => 'cross', 'history' => 'history',
        'commerce' => 'briefcase', 'business' => 'briefcase', 'chemistry' => 'flask', 'biology' => 'dna',
        'physics' => 'atom', 'math' => 'calculator', 'citizenship' => 'users', 'science' => 'flask',
        'social studies' => 'building', 'computer' => 'laptop', 'ict' => 'laptop', 're' => 'cross',
    ];
    $lower = strtolower(trim((string)$name));
    foreach ($map as $key => $icon) {
        if (strpos($lower, $key) !== false) return svg_icon($icon, $class);
    }
    return svg_icon('open-book', $class);
}

function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

function current_customer() {
    global $pdo;
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    return $stmt->fetch();
}

function is_admin() {
    return isset($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin()) redirect('login.php');
}

function require_customer() {
    if (!is_logged_in()) redirect('login.php');
}

function get_cart() {
    return $_SESSION['cart'] ?? [];
}

function set_cart($cart) {
    $_SESSION['cart'] = $cart;
}

function add_to_cart($book_id) {
    $cart = get_cart();
    if (!in_array($book_id, $cart)) {
        $cart[] = $book_id;
    }
    set_cart($cart);
}

function cart_total() {
    global $pdo;
    $cart = get_cart();
    if (empty($cart)) return 0;
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT SUM(price) AS total FROM books WHERE id IN ($placeholders)");
    $stmt->execute($cart);
    $row = $stmt->fetch();
    return $row['total'] ?? 0;
}

function remove_from_cart($book_id) {
    $cart = get_cart();
    $key = array_search((int)$book_id, $cart);
    if ($key !== false) {
        unset($cart[$key]);
        set_cart(array_values($cart));
    }
}

function clear_cart() {
    unset($_SESSION['cart']);
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

function get_book_rating($book_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT AVG(rating) AS avg_rating, COUNT(*) AS total
        FROM reviews
        WHERE book_id = ? AND status = 'approved'
    ");
    $stmt->execute([$book_id]);
    $row = $stmt->fetch();
    return [
        'average' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0,
        'total'   => (int)$row['total'],
    ];
}

function get_book_reviews($book_id, $limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT r.*, c.name AS customer_name, c.class_level
        FROM reviews r
        JOIN customers c ON c.id = r.customer_id
        WHERE r.book_id = ? AND r.status = 'approved'
        ORDER BY r.created_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute([$book_id]);
    return $stmt->fetchAll();
}

function render_stars($average, $total = null, $size = 'md') {
    $size_class = 'stars-' . $size;
    $full_stars = floor($average);
    $has_half = ($average - $full_stars) >= 0.25 && ($average - $full_stars) < 0.75;
    $has_full_extra = ($average - $full_stars) >= 0.75;

    if ($has_full_extra) {
        $full_stars++;
        $has_half = false;
    }

    $html = '<div class="stars-wrapper ' . $size_class . '" aria-label="Rating ' . $average . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            $html .= '<svg viewBox="0 0 24 24" class="star star-full" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } elseif ($has_half && $i == $full_stars + 1) {
            $gid = uniqid('halfGrad');
            $html .= '<svg viewBox="0 0 24 24" class="star star-half"><defs><linearGradient id="' . $gid . '"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#e5e7eb"/></linearGradient></defs><path fill="url(#' . $gid . ')" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } else {
            $html .= '<svg viewBox="0 0 24 24" class="star star-empty" fill="#e5e7eb"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        }
    }
    if ($total !== null) {
        $html .= '<span class="stars-count">' . $total . ' review' . ($total === 1 ? '' : 's') . '</span>';
    }
    $html .= '</div>';
    return $html;
}

function get_book_badge($book_id) {
    global $pdo;
    // New: added within 30 days
    $stmt = $pdo->prepare("SELECT created_at FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $created = $stmt->fetchColumn();
    if ($created && strtotime($created) > strtotime('-30 days')) {
        return ['label' => 'New', 'class' => 'badge-new'];
    }
    // Bestseller: top 20% by real sales
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE oi.book_id = ? AND o.status = 'confirmed'
    ");
    $stmt->execute([$book_id]);
    $sales = (int)$stmt->fetchColumn();
    if ($sales >= 10) {
        return ['label' => 'Bestseller', 'class' => 'badge-bestseller'];
    }
    return null;
}

function get_book_download_count($book_id, $days = 30) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM download_log
        WHERE book_id = ? AND created_at >= NOW() - (? * INTERVAL '1 day')
    ");
    $stmt->execute([$book_id, $days]);
    return (int)$stmt->fetchColumn();
}

function get_book_download_count_total($book_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM download_log WHERE book_id = ?");
    $stmt->execute([$book_id]);
    return (int)$stmt->fetchColumn();
}

function get_trust_signals($book_id = null) {
    $signals = [];

    // Trusted students count (only if real and > 0)
    $students = (int)setting('trust_students_count', '0');
    if ($students > 0) {
        $signals[] = [
            'icon' => 'users',
            'label' => 'Trusted by ' . number_format($students) . '+ South Sudan students',
            'type'  => 'stat',
        ];
    }

    // National curriculum alignment
    if (setting('trust_show_curriculum', '0') === '1') {
        $signals[] = [
            'icon' => 'open-book',
            'label' => 'Aligned with the National Curriculum',
            'type'  => 'claim',
        ];
    }

    // Ministry verification (only if true)
    if (setting('trust_show_ministry', '0') === '1') {
        $signals[] = [
            'icon' => 'check',
            'label' => 'Verified by Ministry of General Education',
            'type'  => 'claim',
        ];
    }

    // Money-back guarantee
    if (setting('trust_show_moneyback', '0') === '1') {
        $days = (int)setting('trust_moneyback_days', '7');
        $signals[] = [
            'icon' => 'discount',
            'label' => $days . '-Day Money-Back Guarantee',
            'type'  => 'claim',
            'tooltip' => setting('trust_moneyback_text', ''),
        ];
    }

    // Real download count for this book
    if ($book_id && setting('trust_show_downloads', '1') === '1') {
        $count = get_book_download_count($book_id, 30);
        if ($count >= 5) {
            $signals[] = [
                'icon' => 'download',
                'label' => 'Downloaded ' . number_format($count) . ' times this month',
                'type'  => 'stat',
            ];
        }
    }

    return $signals;
}

function render_trust_strip($book_id = null) {
    $signals = get_trust_signals($book_id);
    if (empty($signals)) return '';

    $html = '<div class="trust-strip">';
    foreach ($signals as $s) {
        $title = !empty($s['tooltip']) ? ' title="' . htmlspecialchars($s['tooltip']) . '"' : '';
        $html .= '<span class="trust-item trust-' . $s['type'] . '"' . $title . '>';
        $html .= '<span class="trust-icon">' . svg_icon($s['icon']) . '</span>';
        $html .= '<span class="trust-label">' . htmlspecialchars($s['label']) . '</span>';
        $html .= '</span>';
    }
    $html .= '</div>';
    return $html;
}

function render_trust_badges($book_id = null) {
    $html = '';
    if ($book_id) {
        $badge = get_book_badge($book_id);
        if ($badge) {
            $html .= '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
        }
    }
    return $html;
}

function flash($type, $message, $title = '') {
    if (empty($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][] = [
        'type'    => $type,
        'message' => $message,
        'title'   => $title,
    ];
}

function get_flashes() {
    if (empty($_SESSION['flash'])) return [];
    $flashes = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flashes;
}

function render_flashes() {
    $flashes = get_flashes();
    if (empty($flashes)) return '';
    $json = json_encode($flashes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    return '<script>window.__flashMessages = ' . $json . ';</script>';
}

function apply_coupon($code, $cart_total) {
    global $pdo;
    $code = strtoupper(trim($code));
    if ($code === '') return ['ok' => false, 'error' => 'Please enter a coupon code.'];
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) {
        return ['ok' => false, 'error' => 'Invalid coupon code.'];
    }
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        return ['ok' => false, 'error' => 'This coupon has expired.'];
    }
    if ($coupon['max_uses'] !== null && $coupon['uses_count'] >= $coupon['max_uses']) {
        return ['ok' => false, 'error' => 'This coupon has reached its usage limit.'];
    }
    if ($cart_total < (float)$coupon['min_order']) {
        return ['ok' => false, 'error' => 'Your order does not meet the minimum for this coupon.'];
    }
    $discount = 0;
    if ($coupon['discount_type'] === 'percent') {
        $discount = $cart_total * ((float)$coupon['discount_value'] / 100);
    } else {
        $discount = (float)$coupon['discount_value'];
    }
    $discount = min($discount, $cart_total);
    return [
        'ok' => true,
        'coupon' => $coupon,
        'discount' => round($discount, 2),
        'new_total' => round($cart_total - $discount, 2),
    ];
}

function get_applied_coupon() {
    return $_SESSION['coupon'] ?? null;
}

function set_applied_coupon($couponData) {
    $_SESSION['coupon'] = $couponData;
}

function clear_applied_coupon() {
    unset($_SESSION['coupon']);
}

function increment_coupon_usage($couponId) {
    global $pdo;
    $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?")->execute([$couponId]);
}

function maybe_create_review_prompt($customer_id, $book_id, $order_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE customer_id = ? AND book_id = ?");
    $stmt->execute([$customer_id, $book_id]);
    if ($stmt->fetch()) return;
    $stmt = $pdo->prepare("SELECT id FROM review_prompts WHERE customer_id = ? AND book_id = ?");
    $stmt->execute([$customer_id, $book_id]);
    if ($stmt->fetch()) return;
    $stmt = $pdo->prepare("INSERT INTO review_prompts (customer_id, book_id, order_id, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$customer_id, $book_id, $order_id]);
}

function get_pending_review_prompt($customer_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT rp.*, b.title AS book_title, b.cover_image, o.id AS order_id
        FROM review_prompts rp
        JOIN books b ON b.id = rp.book_id
        JOIN orders o ON o.id = rp.order_id
        WHERE rp.customer_id = ?
          AND rp.status = 'pending'
          AND (rp.snoozed_until IS NULL OR rp.snoozed_until <= NOW())
        ORDER BY rp.created_at ASC
        LIMIT 1
    ");
    $stmt->execute([$customer_id]);
    return $stmt->fetch();
}

function mark_review_prompt_shown($prompt_id) {
    global $pdo;
    $pdo->prepare("UPDATE review_prompts SET shown_at = NOW() WHERE id = ?")->execute([$prompt_id]);
}

function snooze_review_prompt($prompt_id, $days = 7) {
    global $pdo;
    $pdo->prepare("UPDATE review_prompts SET status = 'snoozed', snoozed_until = NOW() + (? * INTERVAL '1 day') WHERE id = ?")->execute([$days, $prompt_id]);
    $pdo->prepare("UPDATE review_prompts SET status = 'pending' WHERE id = ? AND status = 'snoozed' AND snoozed_until <= NOW()")->execute([$prompt_id]);
}

function dismiss_review_prompt($prompt_id) {
    global $pdo;
    $pdo->prepare("UPDATE review_prompts SET status = 'dismissed' WHERE id = ?")->execute([$prompt_id]);
}

function refresh_review_prompts($customer_id) {
    global $pdo;
    $pdo->prepare("
        UPDATE review_prompts
        SET status = 'pending'
        WHERE customer_id = ?
          AND status = 'snoozed'
          AND snoozed_until IS NOT NULL
          AND snoozed_until <= NOW()
    ")->execute([$customer_id]);
}

function notify($userType, $userId, $type, $title, $message, $link = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_type, user_id, type, title, message, link)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userType, $userId, $type, $title, $message, $link]);
}

function notify_all_customers($type, $title, $message, $link = null) {
    global $pdo;
    $stmt = $pdo->query("SELECT id FROM customers");
    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("
        INSERT INTO notifications (user_type, user_id, type, title, message, link)
        VALUES ('customer', ?, ?, ?, ?, ?)
    ");
    foreach ($customers as $cid) {
        $ins->execute([$cid, $type, $title, $message, $link]);
    }
}

function notify_all_admins($type, $title, $message, $link = null) {
    global $pdo;
    $stmt = $pdo->query("SELECT id FROM admins");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("
        INSERT INTO notifications (user_type, user_id, type, title, message, link)
        VALUES ('admin', ?, ?, ?, ?, ?)
    ");
    foreach ($admins as $aid) {
        $ins->execute([$aid, $type, $title, $message, $link]);
    }
}

function get_unread_count($userType, $userId) {
    global $pdo;
    if ($userType === 'admin') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_type = 'admin' AND user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    $stmt = $pdo->prepare("SELECT last_read_at FROM notification_reads WHERE user_type = 'customer' AND user_id = ?");
    $stmt->execute([$userId]);
    $lastRead = $stmt->fetchColumn();
    if (!$lastRead) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_type = 'customer' AND user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE user_type = 'customer' AND user_id = ? AND created_at > ?
    ");
    $stmt->execute([$userId, $lastRead]);
    return (int)$stmt->fetchColumn();
}

function get_notifications($userType, $userId, $limit = 20) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_type = ? AND user_id = ?
        ORDER BY created_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute([$userType, $userId]);
    return $stmt->fetchAll();
}

function mark_customer_notifications_read($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notification_reads (user_type, user_id, last_read_at)
        VALUES ('customer', ?, NOW())
        ON CONFLICT (user_type, user_id) DO UPDATE SET last_read_at = NOW()
    ");
    $stmt->execute([$userId]);
}

function mark_admin_notification_read($id) {
    global $pdo;
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = 'admin'")->execute([$id]);
}

function mark_all_admin_notifications_read($userId) {
    global $pdo;
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_type = 'admin' AND user_id = ?")->execute([$userId]);
}
