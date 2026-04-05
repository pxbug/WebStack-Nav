<?php
$page_title = '修改密码';
require_once __DIR__ . '/header.php';

$pdo = pdo_connect();

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pwd  = $_POST['old_password']  ?? '';
    $new_pwd  = $_POST['new_password']  ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($old_pwd === '' || $new_pwd === '' || $confirm === '') {
        $err = '请填写所有字段';
    } elseif (strlen($new_pwd) < 6) {
        $err = '新密码长度不能少于 6 位';
    } elseif ($new_pwd !== $confirm) {
        $err = '两次输入的新密码不一致';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($old_pwd, $row['password'])) {
            $err = '原密码错误';
        } else {
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")
                ->execute([$hash, $_SESSION['admin_id']]);
            session_destroy();
            header('Location: login.php?msg=' . urlencode('密码已修改，请重新登录'));
            exit;
        }
    }
}
?>
<style>
.pw-form { max-width: 420px; }
.form-row { margin-bottom: 18px; }
.form-row label {
    display: block;
    font-size: 13px;
    color: #6b6b6b;
    margin-bottom: 6px;
}
.form-row input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}
.form-row input:focus { border-color: #c9a227; }
.alert { padding: 10px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
.alert-error { background: #fff2f2; color: #e03e3e; }
.btn-save {
    padding: 10px 28px;
    background: #c9a227;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-save:hover { background: #b8922a; }
</style>

<div class="card">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:20px;color:#333;">修改密码</h2>

    <?php if ($err): ?>
    <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST" class="pw-form">
        <div class="form-row">
            <label>当前密码</label>
            <input type="password" name="old_password" placeholder="请输入当前密码" required>
        </div>
        <div class="form-row">
            <label>新密码</label>
            <input type="password" name="new_password" placeholder="请输入新密码（至少 6 位）" required minlength="6">
        </div>
        <div class="form-row">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" placeholder="请再次输入新密码" required minlength="6">
        </div>
        <button type="submit" class="btn-save">确认修改</button>
    </form>
</div>

</div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end admin-layout -->
</body>
</html>
