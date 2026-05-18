<?php
require_once 'db.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

$countSql = "
SELECT COUNT(DISTINCT b.id)
FROM books b
LEFT JOIN book_categories bc ON b.id = bc.book_id
LEFT JOIN categories c ON c.id = bc.category_id
WHERE 1=1
";

$countParams = [];

// SEARCH
if ($search) {
    $countSql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $searchTerm = "%$search%";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}

// CATEGORY FILTER
if ($category) {
    $countSql .= " AND b.id IN (
        SELECT book_id FROM book_categories bc
        JOIN categories c ON bc.category_id = c.id
        WHERE c.name = ?
    )";
    $countParams[] = $category;
}

$stmt = $pdo->prepare($countSql);
$stmt->execute($countParams);
$totalBooks = $stmt->fetchColumn();

$totalPages = ceil($totalBooks / $limit);

// Build query
$sql = "
SELECT 
    b.*,
    GROUP_CONCAT(c.name SEPARATOR ', ') AS categories
FROM books b
LEFT JOIN book_categories bc ON b.id = bc.book_id
LEFT JOIN categories c ON c.id = bc.category_id
WHERE 1=1
";

$params = [];

// SEARCH
if ($search) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// CATEGORY FILTER (FIXED)
if ($category) {
    $sql .= " AND b.id IN (
        SELECT book_id FROM book_categories bc
        JOIN categories c ON bc.category_id = c.id
        WHERE c.name = ?
    )";
    $params[] = $category;
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get all categories for filter
$catStmt = $pdo->query("SELECT name FROM categories ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ቤተ-መፅሐፍት</title>
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
            padding: 20px 32px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .navbar h1 { font-size: 26px; font-weight: 700; }
        .navbar p { opacity: 0.9; font-size: 14px; margin-top: 4px; }
        .nav-logo {
            height: 80px;        
            width: auto;
        }
        .admin-link {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .admin-link:hover { background: rgba(255,255,255,0.3); }

        .search-section {
            background: white;
            padding: 24px 32px;
            border-bottom: 1px solid #eee;
        }
        .search-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .search-select {
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            background: white;
            min-width: 160px;
        }
        .search-btn {
            padding: 12px 28px;
            background: linear-gradient(135deg, #0268bb 100%, #e40f3d 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .clear-btn {
            padding: 12px 20px;
            background: #f0f0f0;
            color: #555;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
        }
        .clear-btn:hover { background: #e0e0e0; }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }
        .results-info {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }
        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .book-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        .book-cover-wrapper {
            position: relative;
            padding-top: 140%;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            overflow: hidden;
        }
        .book-cover-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .book-cover-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #ccc;
        }
        .book-info {
            padding: 16px;
        }
        .book-info h3 {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .book-info .author {
            font-size: 13px;
            color: #888;
            margin-bottom: 8px;
        }
        .book-info .category {
            display: inline-block;
            padding: 3px 10px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        .pdf-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 20px; }
        .empty-state h2 { color: #555; margin-bottom: 8px; }
        .empty-state p { color: #888; }

        @media (max-width: 768px) {
            .navbar-content { flex-direction: column; gap: 12px; text-align: center; }
            .search-container { flex-direction: column; }
            .search-input { width: 100%; }
            .container { padding: 16px; }
            .book-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
        }
        .categories {
            margin-top: 6px;
        }
        .pagination {
            margin-top: 30px;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            margin: 4px;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            background: #f0f0f0;
            color: #333;
            font-size: 14px;
        }
        .pagination a.active {
            background: #667eea;
            color: white;
        }
        .pagination a:hover {
            background: #ddd;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <img src="./images/logo.png" alt="Library Logo" class="nav-logo">
            <div class="brand-text">
                <h1>ቂርቆስ ክ/ከተማ ወረዳ 04</h1>
                <p>ወጣት ስብዕና መገንቢያ ማዕከል ቤተ-መጻሕፍት</p>
            </div>
        </div>
    </nav>

    <div class="search-section">
        <form class="search-container" method="GET" action="">
            <input type="text" name="search" class="search-input" 
                   placeholder="በመፅሐፍ ስም ፣ በደራሲ ወይም በመግለጫ ይፈልጉ..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <select name="category" class="search-select">
                <option value="">ከሁሉም</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                        <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="search-btn">🔍 ፈልግ</button>
            <?php if ($search || $category): ?>
                <a href="index.php" class="clear-btn">አጥፋ</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="container">
        <?php if ($search || $category): ?>
            <p class="results-info">
                Found <?php echo count($books); ?> result(s)
                <?php if ($search): ?> for "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                <?php if ($category): ?> in "<?php echo htmlspecialchars($category); ?>"<?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if (count($books) > 0): ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <a href="details.php?id=<?php echo $book['id']; ?>" class="book-card">
                        <div class="book-cover-wrapper">
                            <?php if ($book['cover_image'] && file_exists($book['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php else: ?>
                                <div class="book-cover-placeholder">📖</div>
                            <?php endif; ?>
                            <?php if ($book['file_path'] && file_exists($book['file_path'])): ?>
                                <span class="pdf-badge">PDF</span>
                            <?php endif; ?>
                        </div>
                        <div class="book-info">
                            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                            <div class="categories">
                                <?php
                                $cats = explode(',', $book['categories'] ?? '');
                                foreach ($cats as $cat) {
                                    $cat = trim($cat);
                                    if ($cat) {
                                        echo "<span class='category'>" . htmlspecialchars($cat) . "</span>";}
                                }
                                ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">← ወደኋላ</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"
           class="<?= $i == $page ? 'active' : '' ?>">
           <?= $i ?>
        </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">ቀጣይ →</a>
        <?php endif; ?>
    </div>

        <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h2>ምንም መፅሐፍ አልተገኘም!</h2>
                <p>የፊደል ፣ የቃላት ስህተት ካለ ይመልከቱ ወይም አዲስ መፅሐፍ ስናስገባ በድጋሜ ይሞክሩ፡፡</p>
                
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
