<?php
require_once __DIR__ . '/includes/functions.php';
require_customer();

$customer = current_customer();
$bookId  = (int)($_GET['book_id'] ?? 0);
$orderId = (int)($_GET['order_id'] ?? 0);
$linkId  = (int)($_GET['link_id'] ?? 0);

if (!$bookId || !$orderId) {
    http_response_code(400);
    exit('Invalid download request.');
}

// 1. Verify ownership
$stmt = $pdo->prepare("
    SELECT o.id
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.id = ? AND o.customer_id = ? AND oi.book_id = ? AND o.status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$orderId, $customer['id'], $bookId]);
if (!$stmt->fetch()) {
    // show locked page
    $pageTitle = 'Download Locked';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="card" style="text-align:center;padding:60px 30px;">
      <div style="font-size:4rem;margin-bottom:16px;"><?= svg_icon('lock', 'icon-xl') ?></div>
      <h1 style="font-size:1.6rem;margin-bottom:12px;">Download Locked</h1>
      <p style="color:var(--text-muted);max-width:500px;margin:0 auto 24px;">
        Your payment has not been confirmed yet.
      </p>
      <a href="account.php" class="btn btn-large">Go to My Account</a>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

// ============================================
// DIRECT FILE DOWNLOAD (link_id present)
// ============================================
if ($linkId) {
    $stmt = $pdo->prepare("SELECT * FROM download_links WHERE id = ? AND book_id = ?");
    $stmt->execute([$linkId, $bookId]);
    $link = $stmt->fetch();
    if (!$link) { http_response_code(404); exit('Link not found.'); }

    if (!function_exists('curl_init')) {
        die('cURL is not enabled. Enable extension=curl in php.ini and restart the server.');
    }

    $url = $link['url'];

    // FIX: robust file ID extraction
    function drive_file_id($url) {
        if (preg_match('#/d/([a-zA-Z0-9_-]{10,})#', $url, $m)) return $m[1];
        if (preg_match('#[?&]id=([a-zA-Z0-9_-]{10,})#', $url, $m)) return $m[1];
        return null;
    }

    $fileId = drive_file_id($url);

    // Build a clean filename from the book title
    $rawName = trim($book['title'] ?: $link['label'] ?: 'study-guide');

    // If the label has something meaningful (not "Download Now"), prefer it
    $labelClean = trim($link['label'] ?? '');
    if ($labelClean && !preg_match('/^(download|click|get|here|file|link|pdf|open)/i', $labelClean)) {
        $rawName = $labelClean;
    }

    // Clean up: remove unsafe characters, collapse spaces to hyphens
    $filename = preg_replace('/[^A-Za-z0-9._ -]+/', '', $rawName);
    $filename = preg_replace('/\s+/', '-', $filename);
    $filename = trim($filename, '-_.');

    if ($filename === '') {
        $filename = 'study-guide';
    }

    // Add .pdf if no extension
    if (!preg_match('/\.(pdf|zip|docx?|pptx?|xlsx?|epub)$/i', $filename)) {
        $filename .= '.pdf';
    }

    // FIX: better error reporting while downloading
    if (!$fileId) {
        // Non-Drive URL, plain proxy
        stream_external_file($url, $filename);
        log_download($pdo, $customer['id'], $bookId, $linkId);
maybe_create_review_prompt($customer['id'], $bookId, $orderId);
        exit;
    }

    // Google Drive — fetch the warning page first, extract tokens
    $cookies = sys_get_temp_dir() . '/gdrive_' . session_id() . '_' . $linkId . '.txt';

    $base = 'https://drive.usercontent.google.com/download?id=' . $fileId . '&export=download&authuser=0&confirm=t';

    // FIX: use CURLOPT_HEADER to capture response headers and detect HTML
    $ch = curl_init($base);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR      => $cookies,
        CURLOPT_COOKIEFILE     => $cookies,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_RANGE          => '0-1024', // FIX: only fetch first 1KB to peek
    ]);
    $peek = curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    // FIX: detect if Google returned HTML
    $isHtml = false;
    if (stripos($contentType ?? '', 'text/html') !== false) {
        $isHtml = true;
    } elseif ($peek && (stripos($peek, '<!DOCTYPE') !== false || stripos($peek, '<html') !== false)) {
        $isHtml = true;
    }

    if ($isHtml) {
        // Extract the confirm token from the HTML response
        $confirmToken = null;
        if (preg_match('/name="confirm"\s+value="([^"]+)"/', $peek, $m)) {
            $confirmToken = $m[1];
        } elseif (preg_match('/confirm=([0-9A-Za-z_-]+)/', $peek, $m)) {
            $confirmToken = $m[1];
        }
        $uuid = null;
        if (preg_match('/name="uuid"\s+value="([^"]+)"/', $peek, $m)) {
            $uuid = $m[1];
        }

        if (!$confirmToken && !$uuid) {
            // Google returned an error page (file not shared, deleted, or private)
            @unlink($cookies);
            http_response_code(500);
            exit('
            <div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto;">
              <h2>Could not download this file</h2>
              <p>Google Drive is blocking this download. This usually means:</p>
              <ul>
                <li>The file is <strong>not shared</strong> publicly. Fix: open the file in Google Drive → Share → General access → <strong>Anyone with the link</strong> → Viewer.</li>
                <li>The file was <strong>deleted</strong> or moved.</li>
                <li>The file is <strong>too large</strong> for Google to scan. Fix: upload it again with a different filename, or split it into smaller parts.</li>
              </ul>
              <p>Ask the admin to re-check the Google Drive link.</p>
              <a href="account.php" style="display:inline-block;margin-top:20px;padding:10px 20px;background:#e63946;color:#fff;border-radius:8px;text-decoration:none;">Back to My Account</a>
            </div>');
        }

        // Re-request with the token
        $finalUrl = $base;
        if ($confirmToken) $finalUrl .= '&confirm=' . urlencode($confirmToken);
        if ($uuid) $finalUrl .= '&uuid=' . urlencode($uuid);

        stream_external_file($finalUrl, $filename, $cookies);
        log_download($pdo, $customer['id'], $bookId, $linkId);
maybe_create_review_prompt($customer['id'], $bookId, $orderId);
        @unlink($cookies);
        exit;
    }

    // Not HTML — the peek told us it's a file. Fetch the full file.
    stream_external_file($base, $filename, $cookies);
    log_download($pdo, $customer['id'], $bookId, $linkId);
maybe_create_review_prompt($customer['id'], $bookId, $orderId);
    @unlink($cookies);
    exit;
}

// ============================================
// SHOW FILE LIST (no link_id)
// ============================================
$stmt = $pdo->prepare("SELECT * FROM download_links WHERE book_id = ? ORDER BY sort_order");
$stmt->execute([$bookId]);
$links = $stmt->fetchAll();

if ($book['type'] === 'bundle') {
    $stmt = $pdo->prepare("
        SELECT dl.*, b.title AS book_title FROM download_links dl
        JOIN books b ON b.id = dl.book_id
        JOIN bundle_items bi ON bi.book_id = dl.book_id
        WHERE bi.bundle_id = ?
    ");
    $stmt->execute([$bookId]);
    $links = $stmt->fetchAll();
}

$pageTitle = 'Download ' . $book['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="steps">
  <div class="step done">1. Details</div>
  <div class="step done">2. Book</div>
  <div class="step done">3. Payment</div>
  <div class="step active">4. Download</div>
</div>

<div class="card" style="text-align:center;padding:40px;">
  <div style="font-size:4rem;margin-bottom:10px;"><?= svg_icon('check-circle', 'icon-xl') ?></div>
  <h1 style="margin-bottom:8px;"><?= clean($book['title']) ?></h1>
  <p style="color:var(--text-muted);margin-bottom:30px;">Payment confirmed. Click below to download.</p>

  <?php if (empty($links)): ?>
    <div class="alert alert-info">No download links yet.</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px;max-width:420px;margin:0 auto;">
      <?php foreach ($links as $link): ?>
        <a href="download.php?book_id=<?= $bookId ?>&order_id=<?= $orderId ?>&link_id=<?= $link['id'] ?>"
           class="btn btn-large btn-block">
          <?= svg_icon('download') ?> <?= clean($link['label'] ?? 'Download Now') ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>


<?php
// ============================================
// HELPER: stream a remote file to the browser
// ============================================
function stream_external_file($url, $filename, $cookies = null) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 600,
        CURLOPT_BUFFERSIZE     => 8192,
    ];
    if ($cookies) {
        $opts[CURLOPT_COOKIEFILE] = $cookies;
        $opts[CURLOPT_COOKIEJAR]  = $cookies;
    }
    curl_setopt_array($ch, $opts);

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
        echo $data;
        return strlen($data);
    });

    curl_exec($ch);
    curl_close($ch);
}

function log_download($pdo, $customerId, $bookId, $linkId) {
    try {
        $stmt = $pdo->prepare("INSERT INTO download_log (customer_id, book_id, link_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$customerId, $bookId, $linkId, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Exception $e) { /* log silently */ }
}