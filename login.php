<?php
require_once 'config/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } elseif (isLoginLocked($username)) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif (authenticate($username, $password)) {
        // SECURITY FIX: the old version passed $_GET['redirect'] straight to
        // the Location header, which allowed an attacker to send a CoK staff
        // member a login link that bounced them to an external phishing page
        // after a successful login. Only same-site relative paths are allowed.
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
        if (!preg_match('#^[A-Za-z0-9_\-./?=&]+$#', $redirect)
            || strpos($redirect, '//') !== false
            || strpos($redirect, ':') !== false
            || strpos($redirect, '..') !== false) {
            $redirect = 'index.php';
        }
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - City of Kigali</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #0033A0 0%, #1A4FB3 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px 45px;
            border-radius: 12px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo .brand-mark {
            display: inline-block;
            width: 60px; height: 60px;
            background: #0033A0; color: #FFCD00;
            border-radius: 8px;
            font-size: 28px; font-weight: 700;
            line-height: 60px;
            margin-bottom: 10px;
        }
        .login-logo h1 { color: #0033A0; font-size: 24px; margin: 0; }
        .login-logo p { color: #666; font-size: 14px; margin: 5px 0 0; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 5px; }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0033A0;
            box-shadow: 0 0 0 3px rgba(0,51,160,0.1);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #0033A0;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover { background: #002266; }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        .footer-text { text-align: center; margin-top: 20px; font-size: 13px; color: #999; }
        .register-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .register-link a { color: #0033A0; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="brand-mark">CoK</div>
            <h1>Inspect</h1>
            <p>City of Kigali Inspection Data Set</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="register-link">
            Password Forgotten <a href="coming_soon.php">Reset Here</a>
        </div>

        <div class="footer-text">
            &copy; <?= date('Y') ?> City of Kigali Inspection Unit
        </div>
    </div>
</body>
</html>