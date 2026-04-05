<?php
session_start();

require_once __DIR__ . '/config.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        try {
            $pdo = pdo_connect();
            $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
            }
        } catch (PDOException $e) {
            $error = '数据库连接失败，请检查配置';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理登录</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
    background: #f0f2f5;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}
.login-box {
    background: #fff;
    padding: 40px 36px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    width: 360px;
}
.login-box h1 {
    font-size: 22px;
    font-weight: 600;
    color: #1a1a1a;
    text-align: center;
    margin-bottom: 28px;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-size: 13px;
    color: #6b6b6b;
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.form-group input:focus {
    border-color: #c9a227;
}
.btn-login {
    width: 100%;
    padding: 12px;
    background: #c9a227;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s;
}
.btn-login:hover { background: #b8922a; }
.error-msg {
    background: #fff2f2;
    color: #e03e3e;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    border: 1px solid #fcd9d9;
}
.success-msg {
    background: #e6f7e6;
    color: #2e8b57;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    border: 1px solid #c8e6c8;
}
</style>
</head>
<body>
<div class="login-box">
    <h1>管理登录</h1>
    <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="success-msg"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" placeholder="请输入用户名" autocomplete="off" required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" placeholder="请输入密码" required>
        </div>
        <button type="submit" class="btn-login">登 录</button>
    </form>
</div>
</body>
</html>
