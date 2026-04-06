<?php
/**
 * 导航站安装程序
 * 访问此文件即可完成数据库配置和管理员账号创建
 */

define('INSTALL_LOCK_FILE', __DIR__ . '/install.lock');

// 已有 lock 文件，跳过安装
if (file_exists(INSTALL_LOCK_FILE)) {
    header('Location: index.php');
    exit;
}

$errors  = [];
$success = '';
$step    = isset($_GET['step']) ? intval($_GET['step']) : 1;

// ===== 安全检查 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    $step = intval($_POST['step']);
}

// ===== Step 3 处理：写入配置 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    $db_host     = trim($_POST['db_host'] ?? 'localhost');
    $db_name     = trim($_POST['db_name'] ?? '');
    $db_user     = trim($_POST['db_user'] ?? '');
    $db_pass     = $_POST['db_pass'] ?? '';
    $admin_user  = trim($_POST['admin_user'] ?? '');
    $admin_pass  = $_POST['admin_pass'] ?? '';
    $admin_pass2 = $_POST['admin_pass2'] ?? '';

    if ($db_name === '') $errors[] = '数据库名不能为空';
    if ($db_user === '') $errors[] = '数据库用户名不能为空';
    if ($admin_user === '') $errors[] = '管理员账号不能为空';
    if (strlen($admin_user) < 3) $errors[] = '管理员账号至少 3 个字符';
    if ($admin_pass === '') $errors[] = '密码不能为空';
    if (strlen($admin_pass) < 6) $errors[] = '密码至少 6 个字符';
    if ($admin_pass !== $admin_pass2) $errors[] = '两次输入的密码不一致';

    if (empty($errors)) {
        // 测试数据库连接
        try {
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Exception $e) {
            $errors[] = '数据库连接失败：' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        // 写入 config.php
        $config_content = "<?php
define('DB_HOST', '" . addslashes($db_host) . "');
define('DB_NAME', '" . addslashes($db_name) . "');
define('DB_USER', '" . addslashes($db_user) . "');
define('DB_PASS', '" . addslashes($db_pass) . "');
define('DB_CHARSET', 'utf8mb4');

function pdo_connect() {
    static \$pdo = null;
    if (\$pdo === null) {
        \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
    }
    return \$pdo;
}

function normalize_public_path(\$path) {
    \$path = trim((string)\$path);
    if (\$path === '') return '';
    if (preg_match('#^https?://#i', \$path) || (strlen(\$path) >= 2 && substr(\$path, 0, 2) === '//')) return \$path;
    return '/' . ltrim(\$path, '/');
}

function init_database() {
    \$pdo = pdo_connect();
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(50) NOT NULL,
            `sort_order` INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `nav_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(100) NOT NULL,
            `subtitle` VARCHAR(200) DEFAULT '',
            `icon` MEDIUMTEXT DEFAULT '',
            `link` VARCHAR(500) NOT NULL,
            `sort_order` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `feeds` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `images` TEXT DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `lottery_prob` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `prob` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `carousel` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `image_url` VARCHAR(500) NOT NULL,
            `link` VARCHAR(500) DEFAULT '',
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"
        CREATE TABLE IF NOT EXISTS `contacts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(20) NOT NULL,
            `label` VARCHAR(50) NOT NULL,
            `value` VARCHAR(255) NOT NULL,
            `icon_bg` VARCHAR(20) DEFAULT '',
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    \");
    \$pdo->exec(\"INSERT IGNORE INTO lottery_prob (prob) SELECT 10.00 WHERE NOT EXISTS (SELECT 1 FROM lottery_prob)\");
    \$pdo->exec(\"INSERT IGNORE INTO admins (username, password) VALUES ('\" . addslashes($admin_user) . "', '\" . password_hash($admin_pass, PASSWORD_DEFAULT) . "')\");
    \$pdo->exec(\"INSERT IGNORE INTO categories (slug, name, sort_order) VALUES ('market','大盘推荐',1),('sim','模拟器',2),('night','深夜福利',3)\");
    \$pdo->exec(\"INSERT IGNORE INTO contacts (type, label, value, icon_bg, sort_order) VALUES
        ('ww','旺旺','888888','#FF5000',1),
        ('tg','TG','iTroll888','#0088cc',2),
        ('email','邮箱','example@qq.com','#c9a227',3)
    \");
}

// 安装锁定文件
file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s'));
header('Location: install.php?step=4');
exit;
}

// ===== Step 4 =====
if ($step === 4) {
    $step = 4;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>安装导航站</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
    background: #0d0d14;
    color: #e0e0e0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.wrap {
    width: 100%;
    max-width: 520px;
}
.logo {
    text-align: center;
    margin-bottom: 36px;
}
.logo-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #c9a227, #e8c55a);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 28px;
}
.logo h1 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.05em;
}
.logo p {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}
.card {
    background: #16161f;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.4);
}
.steps {
    display: flex;
    gap: 0;
    margin-bottom: 28px;
    position: relative;
}
.steps::before {
    content: '';
    position: absolute;
    top: 14px;
    left: 40px;
    right: 40px;
    height: 1px;
    background: rgba(255,255,255,0.08);
    z-index: 0;
}
.step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}
.step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #222230;
    border: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin: 0 auto 6px;
    transition: all 0.3s;
}
.step.done .step-num {
    background: #c9a227;
    border-color: #c9a227;
    color: #fff;
}
.step.active .step-num {
    background: #c9a227;
    border-color: #c9a227;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(201,162,39,0.2);
}
.step-label {
    font-size: 11px;
    color: #555;
}
.step.active .step-label { color: #c9a227; }
.step.done .step-label  { color: #888; }
h2 {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #fff;
}
.subtitle {
    font-size: 13px;
    color: #666;
    margin-bottom: 24px;
}
.field {
    margin-bottom: 18px;
}
.field label {
    display: block;
    font-size: 13px;
    color: #888;
    margin-bottom: 6px;
}
.field label span { color: #ff4d4f; margin-left: 2px; }
input {
    width: 100%;
    padding: 10px 14px;
    background: #0d0d14;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #e0e0e0;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}
input:focus { border-color: #c9a227; }
input::placeholder { color: #444; }
.btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #c9a227, #b8922a);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: opacity 0.2s;
    margin-top: 8px;
}
.btn:hover { opacity: 0.9; }
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
    line-height: 1.6;
}
.alert-error {
    background: rgba(255,77,79,0.12);
    border: 1px solid rgba(255,77,79,0.3);
    color: #ff7875;
}
.alert-info {
    background: rgba(201,162,39,0.1);
    border: 1px solid rgba(201,162,39,0.3);
    color: #e8c55a;
}
.success-box {
    text-align: center;
    padding: 12px 0;
}
.success-icon {
    width: 64px;
    height: 64px;
    background: rgba(82,196,26,0.15);
    border: 2px solid #52c41a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 32px;
    color: #52c41a;
}
.success-box h2 { color: #52c41a; margin-bottom: 8px; }
.success-box p { color: #888; font-size: 13px; line-height: 1.7; margin-bottom: 4px; }
.info-table {
    width: 100%;
    margin: 20px 0;
    border-collapse: collapse;
}
.info-table td {
    padding: 8px 12px;
    font-size: 13px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.info-table td:first-child { color: #666; width: 80px; }
.info-table td:last-child { color: #e0e0e0; font-family: monospace; }
.divider {
    height: 1px;
    background: rgba(255,255,255,0.06);
    margin: 20px 0;
}
.link-btn {
    display: inline-block;
    padding: 10px 24px;
    background: #c9a227;
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    margin-top: 16px;
    transition: opacity 0.2s;
}
.link-btn:hover { opacity: 0.85; }
.note {
    font-size: 11px;
    color: #444;
    margin-top: 16px;
    text-align: center;
    line-height: 1.6;
}
</style>
</head>
<body>
<div class="wrap">
    <div class="logo">
        <div class="logo-icon">N</div>
        <h1>导航站</h1>
        <p>安装向导</p>
    </div>
    <div class="card">

        <?php if ($step === 1): ?>
        <div class="steps">
            <div class="step done"><div class="step-num">1</div><div class="step-label">环境检测</div></div>
            <div class="step active"><div class="step-num">2</div><div class="step-label">配置</div></div>
            <div class="step"><div class="step-num">3</div><div class="step-label">完成</div></div>
        </div>

        <h2>开始安装</h2>
        <p class="subtitle">让我们在 30 秒内完成所有配置</p>

        <div class="alert alert-info">
            <b>请确保已完成以下准备：</b><br>
            1. MySQL 数据库已创建（如宝塔面板新建数据库）<br>
            2. 准备好数据库用户名和密码
        </div>

        <button class="btn" onclick="location.href='install.php?step=2'">开始配置 →</button>

        <?php elseif ($step === 2): ?>
        <div class="steps">
            <div class="step done"><div class="step-num">1</div><div class="step-label">环境检测</div></div>
            <div class="step active"><div class="step-num">2</div><div class="step-label">配置</div></div>
            <div class="step"><div class="step-num">3</div><div class="step-label">完成</div></div>
        </div>

        <h2>数据库 &amp; 管理员</h2>
        <p class="subtitle">填写您的 MySQL 数据库信息和管理员账号</p>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>· <?= htmlspecialchars($e) ?><br><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="step" value="3">

            <div class="field">
                <label>数据库地址</label>
                <input type="text" name="db_host" value="localhost" placeholder="通常为 localhost">
            </div>
            <div class="field">
                <label>数据库名 <span>*</span></label>
                <input type="text" name="db_name" value="navigation" placeholder="请输入数据库名" required>
            </div>
            <div class="field">
                <label>数据库用户名 <span>*</span></label>
                <input type="text" name="db_user" value="navigation" placeholder="数据库用户名" required>
            </div>
            <div class="field">
                <label>数据库密码</label>
                <input type="password" name="db_pass" placeholder="数据库密码，留空表示无密码">
            </div>

            <div class="divider"></div>
            <p style="font-size:13px;color:#666;margin-bottom:16px;">管理员账号</p>

            <div class="field">
                <label>管理员账号 <span>*</span></label>
                <input type="text" name="admin_user" value="admin" placeholder="登录后台的用户名" required>
            </div>
            <div class="field">
                <label>登录密码 <span>*</span></label>
                <input type="password" name="admin_pass" placeholder="至少 6 位密码" required minlength="6">
            </div>
            <div class="field">
                <label>确认密码 <span>*</span></label>
                <input type="password" name="admin_pass2" placeholder="再输一次密码" required>
            </div>

            <button type="submit" class="btn">立即安装 →</button>
        </form>

        <?php elseif ($step === 4): ?>
        <div class="steps">
            <div class="step done"><div class="step-num">1</div><div class="step-label">环境检测</div></div>
            <div class="step done"><div class="step-num">2</div><div class="step-label">配置</div></div>
            <div class="step active"><div class="step-num">3</div><div class="step-label">完成</div></div>
        </div>

        <div class="success-box">
            <div class="success-icon">&#10003;</div>
            <h2>安装成功!</h2>
            <p>数据库已配置，管理员账号已创建</p>
        </div>

        <table class="info-table">
            <tr><td>前台地址</td><td>/index.html</td></tr>
            <tr><td>后台地址</td><td>/admin/</td></tr>
            <tr><td>管理员账号</td><td>admin</td></tr>
            <tr><td>默认密码</td><td>（你设置的密码）</td></tr>
        </table>

        <div style="text-align:center;">
            <a href="../index.html" class="link-btn">打开前台</a>
            <a href="login.php" class="link-btn" style="background:#52c41a;margin-left:10px;">进入后台</a>
        </div>

        <p class="note">
            安装程序已自动锁定，如需重新安装请删除 admin/install.lock 文件<br>
            建议：首次登录后立即修改后台密码
        </p>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
