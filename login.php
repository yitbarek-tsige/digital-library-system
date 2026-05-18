<?php
require_once 'db.php';
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (loginAdmin($username, $password, $pdo)) {
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

if (isAdminLoggedIn()) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የአስተዳደር መግቢያ - ቤተ-መፅሐፍት</title>
    <link rel="icon" type="image/x-icon" href="./images/favicon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        .login-logo {
            height: 80px;        
            width: auto;
            display: block;         
            margin-left: auto;     
            margin-right: auto;
        }
        body {
            font-family: Arial;
            background: linear-gradient(135deg, #0268bb 0%, #e40f3d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .login-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 8px;
            font-size: 28px;
        }
        .login-container .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0268bb 100%, #e40f3d 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover { text-decoration: underline; }

    </style>
</head>
<body>
    <div class="login-container">
        <img src="./images/logo.png" alt="Library Logo" class="login-logo">
        <h1>የአስተዳደር መግቢያ</h1>
        <p class="subtitle">የመፅሐፍት ዝርዝር መዝገብ</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">ስም</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">ይለፍ ቃል</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">ይግቡ</button>
        </form>

        <div class="back-link">
            <a href="index.php">← ወደ ቤተ-መፅሐፍ</a>
        </div>
    </div>
</body>
</html>
