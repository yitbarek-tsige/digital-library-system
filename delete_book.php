<?php
require_once 'db.php';
require_once 'auth.php';
requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin.php?error=Invalid book ID.');
    exit;
}

$id = (int)$_GET['id'];

// Fetch book to get file paths
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: admin.php?error=Book not found.');
    exit;
}

// Delete associated files
if ($book['cover_image'] && file_exists($book['cover_image'])) {
    unlink($book['cover_image']);
}
if ($book['file_path'] && file_exists($book['file_path'])) {
    unlink($book['file_path']);
}

// Delete from database
$stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
$stmt->execute([$id]);

header('Location: admin.php?success=መፅሐፉ ተሰርዟል!');
exit;
?>
