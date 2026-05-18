<?php
require_once 'db.php';
require_once 'auth.php';
requireAdmin();

$isEdit = false;

$book = [
    'id' => '',
    'title' => '',
    'author' => '',
    'description' => '',
    'cover_image' => '',
    'file_path' => ''
];

$error = '';
$selected_categories = []; 

// Load book for edit
if (isset($_GET['id'])) {
    $isEdit = true;

    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $book = $stmt->fetch();

    if (!$book) {
        header('Location: admin.php?error=Book not found.');
        exit;
    }

    // Get selected categories
    $stmt = $pdo->prepare("SELECT category_id FROM book_categories WHERE book_id = ?");
    $stmt->execute([$book['id']]);
    $selected_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $categories = $_POST['categories'] ?? []; // ✅ CHECKBOX VALUES
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($title) || empty($author) || empty($categories)) {
        $error = 'Title, author, and categories are required.';
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $coverImage = $_POST['existing_cover'] ?? '';
    $filePath = $_POST['existing_file'] ?? '';

    // Upload cover image
    if (!$error && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {

        $coverName = time() . '_cover_' . basename($_FILES['cover_image']['name']);
        $coverTarget = $uploadDir . $coverName;

        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['cover_image']['tmp_name']);

        if (in_array($fileType, $allowedImageTypes)) {
            move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverTarget);
            $coverImage = $coverTarget;
        } else {
            $error = 'Invalid image format.';
        }
    }

    // Upload PDF
    if (!$error && isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {

        $fileName = time() . '_book_' . basename($_FILES['book_file']['name']);
        $fileTarget = $uploadDir . $fileName;

        $fileType = mime_content_type($_FILES['book_file']['tmp_name']);

        if ($fileType === 'application/pdf') {
            move_uploaded_file($_FILES['book_file']['tmp_name'], $fileTarget);
            $filePath = $fileTarget;
        } else {
            $error = 'Only PDF allowed.';
        }
    }

    if (empty($error)) {

        if ($id) {
            // UPDATE BOOK
            $stmt = $pdo->prepare("
                UPDATE books 
                SET title=?, author=?, description=?, cover_image=?, file_path=? 
                WHERE id=?
            ");
            $stmt->execute([$title, $author, $description, $coverImage, $filePath, $id]);

            $book_id = $id;

            // delete old categories
            $pdo->prepare("DELETE FROM book_categories WHERE book_id=?")
                ->execute([$book_id]);

        } else {
            // INSERT BOOK
            $stmt = $pdo->prepare("
                INSERT INTO books (title, author, description, cover_image, file_path) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $author, $description, $coverImage, $filePath]);

            $book_id = $pdo->lastInsertId();
        }

        // INSERT CATEGORIES 
        if (!empty($categories)) {
            $stmt = $pdo->prepare("
                INSERT INTO book_categories (book_id, category_id)
                VALUES (?, ?)
            ");

            foreach ($categories as $cid) {
                $stmt->execute([$book_id, $cid]);
            }
        }

        header("Location: admin.php?success=መፅሐፍ ተመዝግቧል/ተስተካክሏል!");
        exit;
    }

    // repopulate on error
    $book = [
        'id' => $id,
        'title' => $title,
        'author' => $author,
        'description' => $description,
        'cover_image' => $coverImage,
        'file_path' => $filePath
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit Book' : 'Add New Book'; ?> - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #0268bb 0%, #e40f3d 100%);
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 22px; }
        .navbar a { color: white; text-decoration: none; font-size: 14px; }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        .category-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            max-height: 250px;
            overflow-y: auto;
        }
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 32px;
        }
        .form-card h2 {
            color: #333;
            margin-bottom: 24px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group input[type="file"] {
            padding: 8px 0;
        }
        .file-info {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }
        .current-file {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #e8f5e9;
            border-radius: 6px;
            font-size: 13px;
            color: #2e7d32;
            margin-top: 8px;
        }
        .current-cover {
            margin-top: 8px;
        }
        .current-cover img {
            width: 100px;
            height: 140px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #555;
        }
        .btn-secondary:hover { background: #e0e0e0; }
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 600px) {
            .two-col { grid-template-columns: 1fr; }
            .container { padding: 16px; }
            .form-card { padding: 20px; }
        }

    </style>
</head>
<body>
    <nav class="navbar">
        <h1>የቤተ መፅሐፍት አስተዳደር</h1>
        <a href="admin.php">← ተመለስ</a>
    </nav>

    <div class="container">
        <div class="form-card">
            <h2><?php echo $isEdit ? '✏️ አስተካክል' : '➕ አዲስ መፅሐፍ አስገባ'; ?></h2>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($book['id']); ?>">
                <input type="hidden" name="existing_cover" value="<?php echo htmlspecialchars($book['cover_image']); ?>">
                <input type="hidden" name="existing_file" value="<?php echo htmlspecialchars($book['file_path']); ?>">

                <div class="two-col">
                    <div class="form-group">
                        <label for="title">የመፅሐፍ ስም *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="author">ደራሲ *</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="categories">ዘውግ *</label>
                        <div class="category-box">
                                <?php
                            $stmt = $pdo->query("SELECT * FROM categories");
                            while ($row = $stmt->fetch()) {
                                $checked = in_array($row['id'], $selected_categories ?? []) ? 'checked' : '';
                                echo "
                                <label class='cat-item'>
                                    <input type='checkbox' name='categories[]' value='{$row['id']}' $checked>
                                    <span>{$row['name']}</span>
                                </label> ";
                            }
                            ?>
                        </div>
                </div>

                <div class="form-group">
                    <label for="description">መግለጫ</label>
                    <textarea id="description" name="description" placeholder="የመፅሐፍ መግለጫ ..."><?php echo htmlspecialchars($book['description']); ?></textarea>
                </div>

                <div class="two-col">
                    <div class="form-group">
                        <label for="cover_image">የፊት ሽፋን </label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        <p class="file-info">የተፈቀዱ: JPG, PNG, GIF, WEBP (Max 5MB)</p>
                        <?php if ($book['cover_image'] && file_exists($book['cover_image'])): ?>
                            <div class="current-cover">
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Current Cover">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="book_file">ዲጂታል ኮፒ (PDF)</label>
                        <input type="file" id="book_file" name="book_file" accept=".pdf">
                        <p class="file-info">ለPDF ብቻ የተፈቀደ (Max 50MB)</p>
                        <?php if ($book['file_path'] && file_exists($book['file_path'])): ?>
                            <div class="current-file">
                                📄 <?php echo basename($book['file_path']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? '💾 መፅሐፍ አስተካክል' : '➕ መፅሐፍ አስገባ'; ?>
                    </button>
                    <a href="admin.php" class="btn btn-secondary">ይቅር</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
