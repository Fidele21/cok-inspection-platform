<?php
require_once 'config/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$roles = ['Inspector', 'Senior Inspector', 'Chief Inspector', 'Director'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'Inspector';
    
    if (empty($username) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (!in_array($role, $roles)) {
        $error = 'Invalid role selected';
    } else {
        $result = registerUser($username, $password, $full_name, $email, $role);
        if ($result['success']) {
            $success = 'Registration successful! You can now <a href="login.php">login</a>.';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - City of Kigali</title>
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
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 35px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 480px;
        }
        .register-logo { text-align: center; margin-bottom: 25px; }
        .register-logo .brand-mark {
            display: inline-block;
            width: 50px; height: 50px;
            background: #0033A0; color: #FFCD00;
            border-radius: 8px;
            font-size: 24px; font-weight: 700;
            line-height: 50px;
            margin-bottom: 8px;
        }
        .register-logo h1 { color: #0033A0; font-size: 22px; margin: 0; }
        .register-logo p { color: #666; font-size: 13px; margin: 3px 0 0; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px; }
        .form-group label .required { color: #EF4135; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #0033A0;
            box-shadow: 0 0 0 3px rgba(0,51,160,0.1);
        }
        .form-group .hint { font-size: 12px; color: #999; margin-top: 3px; }
        .btn-register {
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
        .btn-register:hover { background: #002266; }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #c3e6cb;
        }
        .footer-text { text-align: center; margin-top: 20px; font-size: 13px; color: #999; }
        .login-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .login-link a { color: #0033A0; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } .register-container { padding: 25px; } }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">
            <div class="brand-mark">CoK</div>
            <h1>Create Account</h1>
            <p>City of Kigali · Inspection Platform</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" placeholder="Choose a username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" placeholder="Min 4 characters" required>
                    <div class="hint">Minimum 4 characters</div>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Role <span class="required">*</span></label>
                <select name="role" required>
                    <option value="">Select your role</option>
                    <option value="Inspector" <?= (isset($_POST['role']) && $_POST['role'] === 'Inspector') ? 'selected' : '' ?>>Inspector</option>
                    <option value="Senior Inspector" <?= (isset($_POST['role']) && $_POST['role'] === 'Senior Inspector') ? 'selected' : '' ?>>Senior Inspector</option>
                    <option value="Chief Inspector" <?= (isset($_POST['role']) && $_POST['role'] === 'Chief Inspector') ? 'selected' : '' ?>>Chief Inspector</option>
                    <option value="Director" <?= (isset($_POST['role']) && $_POST['role'] === 'Director') ? 'selected' : '' ?>>Director</option>
                </select>
            </div>
            <button type="submit" class="btn-register">📝 Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        <?php endif; ?>

        <div class="footer-text">
            &copy; <?= date('Y') ?> City of Kigali Inspection Unit
        </div>
    </div>
</body>
</html>