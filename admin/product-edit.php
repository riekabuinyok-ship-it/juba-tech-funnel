<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$book = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if (!$book) redirect('products.php');
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY level, name")->fetchAll();
$errors = [];

function handle_cover_upload($fileInput, $existingImage = null) {
    if (empty($_FILES[$fileInput]['name'])) {
        return $existingImage;
    }
    $file = $_FILES[$fileInput];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed with error code ' . $file['error']);
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new Exception('Image too large. Max 3 MB.');
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowed)) {
        throw new Exception('Only JPG, PNG, WEBP, or GIF allowed.');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new Exception('File is not a valid image.');
    }

    $dir = __DIR__ . '/../uploads/covers/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new Exception('Could not save uploaded file.');
    }

    if ($existingImage && file_exists($dir . $existingImage)) {
        @unlink($dir . $existingImage);
    }

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = (int)$_POST['subject_id'];
    $title = trim($_POST['title']);
    $grade = trim($_POST['grade']);
    $description = trim($_POST['description']);
    $priceType = $_POST['price_type'] ?? 'paid';
    if ($priceType === 'free') {
        $price = 0;
    } else {
        $price = (float)$_POST['price'];
    }
    $type = $_POST['type'];
    $status = $_POST['status'];
    $previewUrl = trim($_POST['preview_url'] ?? '');

    if ($title === '') $errors[] = 'Title required';

    $coverImage = $book['cover_image'] ?? null;
    try {
        $coverImage = handle_cover_upload('cover_image', $coverImage);
    } catch (Exception $e) {
        $errors[] = 'Cover image: ' . $e->getMessage();
    }

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE books SET subject_id=?, title=?, grade=?, description=?, price=?, price_type=?, type=?, status=?, cover_image=?, preview_url=? WHERE id=?");
            $stmt->execute([$subjectId, $title, $grade, $description, $price, $priceType, $type, $status, $coverImage, $previewUrl, $id]);
            $bookId = $id;
} else {
    $stmt = $pdo->prepare("INSERT INTO books (subject_id, title, grade, description, price, price_type, type, status, cover_image, preview_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$subjectId, $title, $grade, $description, $price, $priceType, $type, $status, $coverImage, $previewUrl]);
    $bookId = $pdo->lastInsertId();

    // Notify all customers about the new guide
    if ($status === 'active') {
        notify_all_customers(
            'new_book',
            'New Guide Available',
            $title . ' is now available in the store.',
            'sales.php?book_id=' . $bookId
        );
    }
}

        $pdo->prepare("DELETE FROM download_links WHERE book_id = ?")->execute([$bookId]);
        $labels = $_POST['link_label'] ?? [];
        $urls = $_POST['link_url'] ?? [];
        foreach ($urls as $i => $url) {
            if (trim($url) === '') continue;
            $stmt = $pdo->prepare("INSERT INTO download_links (book_id, label, url, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bookId, $labels[$i] ?? 'Download', $url, $i]);
        }

        if ($type === 'bundle' && !empty($_POST['bundle_items'])) {
            $pdo->prepare("DELETE FROM bundle_items WHERE bundle_id = ?")->execute([$bookId]);
            foreach ($_POST['bundle_items'] as $bid) {
                $stmt = $pdo->prepare("INSERT INTO bundle_items (bundle_id, book_id) VALUES (?, ?)");
                $stmt->execute([$bookId, (int)$bid]);
            }
        }

        redirect('products.php');
    }
}

$existingLinks = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM download_links WHERE book_id = ? ORDER BY sort_order");
    $stmt->execute([$id]);
    $existingLinks = $stmt->fetchAll();
}

$pageTitle = $id ? 'Edit Book' : 'Add Book';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <a href="products.php" class="btn btn-outline btn-sm">← Back to Products</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error"><?= implode('<br>', array_map('clean', $errors)) ?></div>
<?php endif; ?>

<section class="admin-section">
  <div class="section-head">
    <h2><?= $id ? 'Edit' : 'Add' ?> Book</h2>
  </div>
  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label>Subject</label>
      <select name="subject_id" required>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($book['subject_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
            <?= clean($s['name']) ?> (<?= clean($s['level']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Title</label>
      <input name="title" required value="<?= clean($book['title'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Grade</label>
      <input name="grade" value="<?= clean($book['grade'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="3"><?= clean($book['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group"><label>Product Type</label>
  <select name="price_type" id="priceTypeSelect">
    <option value="paid" <?= ($book['price_type'] ?? 'paid') === 'paid' ? 'selected' : '' ?>>Paid</option>
    <option value="free" <?= ($book['price_type'] ?? '') === 'free' ? 'selected' : '' ?>>Free</option>
  </select>
</div>

<div class="form-group" id="priceGroup"><label>Price (SSP)</label>
  <input type="number" step="0.01" name="price" value="<?= clean($book['price'] ?? setting('single_price')) ?>"></div>

<script>
(function() {
  var typeSelect = document.getElementById('priceTypeSelect');
  var priceGroup = document.getElementById('priceGroup');
  if (!typeSelect || !priceGroup) return;

  function updatePrice() {
    if (typeSelect.value === 'free') {
      priceGroup.style.display = 'none';
      priceGroup.querySelector('input').value = '0';
    } else {
      priceGroup.style.display = '';
    }
  }
  typeSelect.addEventListener('change', updatePrice);
  updatePrice();
})();
</script>

    <div class="form-group">
      <label>Type</label>
      <select name="type">
        <option value="single" <?= ($book['type'] ?? '') === 'single' ? 'selected' : '' ?>>Single Book</option>
        <option value="bundle" <?= ($book['type'] ?? '') === 'bundle' ? 'selected' : '' ?>>Bundle</option>
      </select>
    </div>

    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="active" <?= ($book['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($book['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>

    <div class="form-group">
      <label>Cover Image (JPG, PNG, WEBP, GIF, max 3 MB)</label>
      <?php if (!empty($book['cover_image'])): ?>
        <div style="margin-bottom:10px;">
          <img src="<?= SITE_URL ?>/uploads/covers/<?= clean($book['cover_image']) ?>"
               alt="Current cover"
               style="max-width:140px;border-radius:8px;border:1px solid var(--border);">
          <p style="font-size:0.8rem;color:var(--text-muted);margin-top:6px;">Current cover. Upload a new file to replace it.</p>
        </div>
      <?php endif; ?>
      <input type="file" name="cover_image" accept="image/*">
    </div>

    <!-- Preview URL -->
    <div class="form-group">
      <label>Preview PDF URL (Google Drive share link)</label>
      <input type="url" name="preview_url"
             placeholder="https://drive.google.com/file/d/XXXX/view?usp=sharing"
             value="<?= clean($book['preview_url'] ?? '') ?>">
      <small style="color:var(--text-muted);font-size:0.78rem;display:block;margin-top:6px;">
        Paste the Google Drive share link for the 3-page preview. Leave blank if no preview.
        The preview file must be set to "Anyone with the link can view" in Google Drive.
      </small>
    </div>

    <h3>Download Links</h3>
    <p class="muted">Paste external links (Google Drive, Dropbox, etc.). Add up to 4 for a bundle.</p>
    <?php for ($i = 0; $i < 4; $i++): ?>
      <div class="form-group" style="display:flex;gap:10px;">
        <input name="link_label[]" placeholder="Label" value="<?= clean($existingLinks[$i]['label'] ?? '') ?>" style="flex:1;">
        <input name="link_url[]" placeholder="https://..." value="<?= clean($existingLinks[$i]['url'] ?? '') ?>" style="flex:3;">
      </div>
    <?php endfor; ?>

    <button class="btn">Save Book</button>
  </form>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>