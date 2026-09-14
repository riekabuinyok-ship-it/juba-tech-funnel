<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        if ($book) {
            $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
            if (!empty($book['cover_image'])) {
                $cover = __DIR__ . '/../uploads/covers/' . $book['cover_image'];
                if (is_file($cover)) @unlink($cover);
            }
            redirect('products.php?deleted=1');
        }
    } catch (Exception $e) {
        redirect('products.php?deleted=error');
    }
}
redirect('products.php');