<?php
require_once 'db.php';
require_once 'auth.php';
requireAdmin();

$error = '';
$success = '';

// ADD CATEGORY
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);

    if ($name == '') {
        $error = "ዘውግ ስም አልተሞላም!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "አዲስ ዘውግ በተሳካ ሁኔታ ተጨምሯል!";
        } catch (Exception $e) {
            $error = "ተመሳሳይ የሆነ ዘውግ አለ።";
        }
    }
}

// DELETE CATEGORY (SAFE POST)
if (isset($_POST['delete'])) {
    $id = $_POST['delete'];

    $pdo->prepare("DELETE FROM book_categories WHERE category_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);

    $success = "ዘውግ በተሳካ ሁኔታ ተሰርዟል!";
}

// FETCH CATEGORIES
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>ቤተ-መፅሐፍት አስተዳደር</title>
    <link rel="icon" type="image/x-icon" href="./images/favicon.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial;
            background: #f5f7fa;
        }

        /* NAV */
        .navbar {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
        }

        /* CONTAINER */
        .container {
            max-width: 900px;
            margin: auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        /* FORM */
        form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            min-width: 200px;
        }

        button {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            background: #667eea;
            color: white;
            cursor: pointer;
        }

        /* TABLE WRAPPER */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 400px;
        }

        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f5f6fa;
            font-weight: 600;
        }

        /* DELETE BUTTON */
        .delete-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }

        /* MESSAGES */
        .msg {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .error { background: #ffe6e6; color: red; }
        .success { background: #e6ffe6; color: green; }

        /* MOBILE */
        @media(max-width:600px){
            .container { padding: 10px; }
            .card { padding: 15px; }
            input { width: 100%; }
            form { flex-direction: column; }
        }
    </style>
</head>

<body>

<div class="navbar">
    <h3>የቤተ መፅሐፍት አስተዳደር</h3>
    <a href="admin.php">← ተመለስ</a>
</div>

<div class="container">
    <div class="card">
        <h2>🏷️ የመፅሐፍት ዘውግ</h2>

        <?php if ($error): ?>
            <div class="msg error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="msg success"><?= $success ?></div>
        <?php endif; ?>

        <!-- ADD -->
        <form method="POST">
            <input type="text" name="name" placeholder="አዲስ ዘውግ">
            <button type="submit" name="add">አስገባ</button>
        </form>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>ስም</th>
                    <th>ተግባር</th>
                </tr>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('ይህን ዘውግ ማስወገድ እርግጠኛ ነዎት?');">
                                <input type="hidden" name="delete" value="<?= $cat['id'] ?>">
                                <button class="delete-btn">ሰርዝ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

</body>
</html>