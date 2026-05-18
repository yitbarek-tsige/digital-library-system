<?php
require_once 'db.php';
require_once 'auth.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

// Pagination
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// BASE QUERY
$sql = "
    FROM books b
    LEFT JOIN book_categories bc ON b.id = bc.book_id
    LEFT JOIN categories c ON bc.category_id = c.id
    WHERE 1=1
";

$params = [];

// SEARCH
if ($search) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

// CATEGORY FILTER
if ($category) {
    $sql .= " AND c.name = ?";
    $params[] = $category;
}

// COUNT total
$countSql = "SELECT COUNT(DISTINCT b.id) " . $sql;
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalBooks = $stmt->fetchColumn();

$totalPages = ceil($totalBooks / $limit);

// FINAL DATA QUERY
$dataSql = "
    SELECT b.*, GROUP_CONCAT(c.name SEPARATOR ', ') AS categories
    $sql
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($dataSql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for dropdown
$catStmt = $pdo->query("SELECT name FROM categories ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Get success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የቤተ መፅሐፍት አስተዳደር</title>
    <link rel="icon" type="image/x-icon" href="./images/favicon.png">
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
        .navbar h1 { font-size: 22px; font-weight: 600; }
        .navbar .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        .navbar a:hover { opacity: 1; }
        .navbar .user-info {
            font-size: 14px;
            opacity: 0.9;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .header-actions h2 {
            color: #333;
            font-size: 24px;
        }
        .btn {
            padding: 12px 24px;
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
        .btn-edit {
            background: #4CAF50;
            color: white;
            padding: 6px 14px;
            font-size: 13px;
        }
        .btn-edit:hover { background: #45a049; }
        .btn-delete {
            background: #f44336;
            color: white;
            padding: 6px 14px;
            font-size: 13px;
        }
        .btn-delete:hover { background: #da190b; }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-bar input,
        .search-bar select {
            padding: 10px 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .search-bar input {
            flex: 1;
            min-width: 200px;
        }
        .search-bar select {
            min-width: 160px;
        }
        .search-bar button {
            white-space: nowrap;
        }
        .pagination {
            margin-top: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
        .pagination a {
            padding: 6px 12px;
            margin: 3px;
            background: #eee;
            border-radius: 5px;
            text-decoration: none;
        }
        .pagination a.active {
            background: #667eea;
            color: white;
        }
        .alert {
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #444;
            vertical-align: middle;
        }
        tr:hover { background: #fafbfc; }
        .book-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .book-cover-placeholder {
            width: 50px;
            height: 70px;
            background: #e0e0e0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 20px;
        }
        .book-title {
            font-weight: 600;
            color: #333;
        }
        .book-author {
            color: #888;
            font-size: 13px;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        td {
            max-width: 175px;
            white-space: normal;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .file-icon {
            color: #4CAF50;
            font-size: 16px;
        }
        .no-file {
            color: #ccc;
            font-size: 16px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 20px;
            color: #555;
            margin-bottom: 8px;
        }
        @media (max-width: 768px) {
            .container { padding: 16px; }
            table { font-size: 12px; }
            th, td { padding: 10px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>የቤተ መፅሐፍት አስተዳደር</h1>
        <div class="nav-links">
            <span class="user-info">ሰላም, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="index.php">← ወደ ቤተ-መፅሐፍ</a>
            <a href="logout.php">ዘግተህ ውጣ</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-actions">
            <h2>ሁሉም መፅሐፍት</h2>
            <div class="action-buttons">
                <a href="admin_categories.php" class="btn btn-primary">🏷️ አዲስ ዘውግ አስገባ</a>
                <a href="book_form.php" class="btn btn-primary">➕ አዲስ መፅሐፍ አስገባ</a>
            </div>
        </div>

        <form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="በመፅሐፍ ስም ፣ በደራሲ ወይም በመግለጫ ይፈልጉ..." 
        value="<?= htmlspecialchars($search) ?>">

    <select name="category">
        <option value="">ሁሉም ዘውጎች</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"
                <?= $category === $cat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-primary">🔍 ፈልግ</button>

    <?php if ($search || $category): ?>
        <a href="admin.php" class="btn">አጥፋ</a>
    <?php endif; ?>
</form>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (count($books) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>የፊት ሽፋን</th>
                            <th>መረጃ</th>
                            <th>ዘውግ</th>
                            <th>ዲጂታል ኮፒ</th>
                            <th>የገባው</th>
                            <th>ተግባራት</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <?php if ($book['cover_image'] && file_exists($book['cover_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Cover" class="book-cover">
                                    <?php else: ?>
                                        <div class="book-cover-placeholder">📖</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($book['categories'])) {
                                        $cats = explode(',', $book['categories']);
                                        foreach ($cats as $cat) {
                                            echo "<span class='category-badge'>" . htmlspecialchars(trim($cat)) . "</span> ";
                                        }
                                    } else {
                                        echo "<span class='category-badge'>No Category</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($book['file_path'] && file_exists($book['file_path'])): ?>
                                        <span class="file-icon" title="PDF Available">📄</span>
                                    <?php else: ?>
                                        <span class="no-file" title="No File">❌</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="book_form.php?id=<?php echo $book['id']; ?>" class="btn btn-edit">አስተካክል</a>
                                        <a href="delete_book.php?id=<?php echo $book['id']; ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('መፅሐፉን ለማጥፋት እርግጠኛ ነዎት? ከተሰረዘ መመለስ አይቻልም');">ሰርዝ</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">←</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"
                        class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">→</a>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>

                <div class="empty-state">
                    <div class="icon">📚</div>
                    <h3>ምንም መፅሐፍት አልተመዘገበም!</h3>
                    <p>የመጀመሪያ መፅሐፍ ወደ ቤተ-መፅሐፍቱ ያስገቡ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
