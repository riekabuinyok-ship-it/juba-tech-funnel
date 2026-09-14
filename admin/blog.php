<?php
$pageTitle = 'Blog';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    redirect('blog.php');
}

if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE blog_posts SET status = CASE WHEN status = 'published' THEN 'draft' ELSE 'published' END WHERE id = ?");
    $stmt->execute([(int)$_GET['toggle']]);
    redirect('blog.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $image = trim($_POST['image'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $editId = (int)($_POST['edit_id'] ?? 0);

    if (!$slug) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }

    if ($title) {
        if ($editId) {
            $stmt = $pdo->prepare("UPDATE blog_posts SET title=?, slug=?, content=?, image=?, status=? WHERE id=?");
            $stmt->execute([$title, $slug, $content, $image, $status, $editId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, image, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $image, $status]);
        }
        redirect('blog.php');
    }
}

$posts = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">Write and publish study tips and announcements for your audience.</p>
</div>

<section class="admin-section">
  <div class="section-head">
    <h2><?= $editing ? 'Edit Post' : 'New Post' ?></h2>
  </div>
  <form method="POST">
    <?php if ($editing): ?>
      <input type="hidden" name="edit_id" value="<?= $editing['id'] ?>">
    <?php endif; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
      <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" required value="<?= clean($editing['title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Slug</label>
        <input type="text" name="slug" value="<?= clean($editing['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>
      <div class="form-group">
        <label>Image Filename</label>
        <input type="text" name="image" value="<?= clean($editing['image'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Content</label>
      <textarea name="content" rows="8"><?= clean($editing['content'] ?? '') ?></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn"><?= $editing ? 'Update Post' : 'Create Post' ?></button>
      <?php if ($editing): ?>
        <a href="blog.php" class="btn btn-outline">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="admin-section">
  <div class="section-head">
    <h2>Posts</h2>
    <span class="count-pill"><?= count($posts) ?> posts</span>
  </div>
  <?php if (empty($posts)): ?>
    <p class="muted">No posts yet.</p>
  <?php else: ?>
    <div class="books-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($posts as $post): ?>
            <tr>
              <td>#<?= $post['id'] ?></td>
              <td><strong><?= clean($post['title']) ?></strong></td>
              <td class="muted"><?= clean($post['slug']) ?></td>
              <td>
                <span class="status-badge status-<?= $post['status'] === 'published' ? 'confirmed' : 'pending' ?>">
                  <?= ucfirst($post['status']) ?>
                </span>
              </td>
              <td class="muted"><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
              <td class="row-actions">
                <a href="blog.php?edit=<?= $post['id'] ?>" class="btn btn-sm">Edit</a>
                <a href="blog.php?toggle=<?= $post['id'] ?>" class="btn btn-sm btn-outline"><?= $post['status'] === 'published' ? 'Unpublish' : 'Publish' ?></a>
                <a href="blog.php?delete=<?= $post['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Delete this post?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>