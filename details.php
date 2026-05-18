<?php
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT 
        b.*,
        GROUP_CONCAT(c.name SEPARATOR ', ') AS categories
    FROM books b
    LEFT JOIN book_categories bc ON b.id = bc.book_id
    LEFT JOIN categories c ON c.id = bc.category_id
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

$hasFile = $book['file_path'] && file_exists($book['file_path']);
$hasCover = $book['cover_image'] && file_exists($book['cover_image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - ቤተ-መፅሐፍት</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px;
        }
        .book-detail {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .book-header {
            display: flex;
            gap: 40px;
            padding: 40px;
        }
        .cover-section {
            flex-shrink: 0;
        }
        .categories {
            margin-bottom: 16px;
        }
        .cover-section img {
            width: 280px;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        .cover-placeholder {
            width: 280px;
            height: 400px;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: #ccc;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .category-tag {
            display: inline-block;
            padding: 6px 16px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            align-self: flex-start;
        }
        .book-title {
            font-size: 32px;
            font-weight: 700;
            color: #222;
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .book-author {
            font-size: 18px;
            color: #666;
            margin-bottom: 24px;
        }
        .book-author strong { color: #333; }
        .description {
            color: #555;
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 32px;
            flex: 1;
        }
        .description h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }
        .btn-download {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.4);
        }
        .btn-disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }
        .btn-back {
            background: #f0f0f0;
            color: #555;
        }
        .btn-back:hover { background: #e0e0e0; }

        .meta-info {
            background: #f8f9fa;
            padding: 20px 40px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .meta-item {
            font-size: 13px;
            color: #888;
        }
        .meta-item strong {
            color: #555;
            margin-right: 4px;
        }

        @media (max-width: 768px) {
            .book-header { flex-direction: column; align-items: center; text-align: center; padding: 24px; }
            .cover-section img, .cover-placeholder { width: 200px; height: 280px; }
            .book-title { font-size: 24px; }
            .category-tag { align-self: center; }
            .actions { justify-content: center; }
            .meta-info { justify-content: center; padding: 16px 24px; }
            .container { padding: 16px; }
        }

    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php">← ወደ መፅሐፍ ማውጫ</a>
            <span style="font-size: 14px; opacity: 0.9;">ቤተ-መፅሐፍት</span>
        </div>
    </nav>

    <div class="container">
        <div class="book-detail">
            <div class="book-header">
                <div class="cover-section">
                    <?php if ($hasCover): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <?php else: ?>
                        <div class="cover-placeholder">📖</div>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <div class="categories">
                        <?php
                        $cats = explode(',', $book['categories'] ?? '');
                        foreach ($cats as $cat) {
                            $cat = trim($cat);
                            if ($cat) {
                                echo "<span class='category-tag'>" . htmlspecialchars($cat) . "</span>"; }
                            }
                            ?>
                    </div>
                    <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="book-author">by <strong><?php echo htmlspecialchars($book['author']); ?></strong></p>

                    <div class="description">
                        <h3>ስለ መፅሐፉ</h3>
                        <?php if ($book['description']): ?>
                            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                        <?php else: ?>
                            <p style="color: #aaa; font-style: italic;">ዝርዝር መግለጫ አልተገኘም!</p>
                        <?php endif; ?>
                    </div>

                    <div class="actions">
                        <?php if ($hasFile): ?>
                            <a href="download.php?id=<?php echo $book['id']; ?>" class="btn btn-download">
                                ⬇️ PDF ያውርዱ
                            </a>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>
                                ❌ PDF አልተገኘም!
                            </button>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-back">← ወደ መፅሐፍ ማውጫ</a>
                    </div>
                </div>
            </div>

            <div class="meta-info">
                <div class="meta-item">
                    <strong>የገባው:</strong> <?php echo date('F d, Y', strtotime($book['created_at'])); ?>
                </div>
                <div class="meta-item">
                    <strong>የመጨረሻ የተሻሻለ:</strong> <?php echo date('F d, Y', strtotime($book['updated_at'])); ?>
                </div>
                <?php if ($hasFile): ?>
                    <div class="meta-item">
                        <strong>File:</strong> PDF Available
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
