<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$err = '';

try {
    $pdo = pdo_connect();
    init_database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $prob = isset($_POST['prob']) ? floatval($_POST['prob']) : 10;
        $prob = max(0, min(100, $prob));

        $stmt = $pdo->prepare("UPDATE lottery_prob SET prob = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prob]);

        header("Location: lottery.php?msg=" . urlencode('设置已保存'));
        exit;
    }

    $row = $pdo->query("SELECT prob FROM lottery_prob ORDER BY id DESC LIMIT 1")->fetch();
    $curProb = $row ? floatval($row['prob']) : 10.0;

    if (isset($_GET['msg'])) {
        $msg = htmlspecialchars($_GET['msg']);
    }

} catch (Exception $e) {
    $db_err = $e->getMessage();
}

$page_title = '抽奖设置';
require_once __DIR__ . '/header.php';
?>
<style>
.btn { padding: 8px 20px; border-radius: 8px; font-size: 14px; cursor: pointer; border: none; font-family: inherit; font-weight: 500; }
.btn-primary { background: #c9a227; color: #fff; }
.btn-primary:hover { background: #b8922a; }
.alert { padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
.alert-error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
.setting-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.setting-row { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.setting-label { font-size: 14px; font-weight: 500; color: #333; min-width: 90px; }
.setting-input { flex: 1; min-width: 120px; }
.setting-input input {
    width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 8px;
    font-size: 14px; font-family: inherit;
}
.setting-input input:focus { outline: none; border-color: #c9a227; }
.setting-unit { font-size: 14px; color: #666; }
.setting-hint { font-size: 12px; color: #999; margin-top: 6px; }
.prize-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 24px; }
.prize-table th { text-align: left; padding: 10px 12px; background: #fafafa; color: #666; font-weight: 500; border-bottom: 1px solid #e8e8e8; }
.prize-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; color: #333; }
.prize-table tr:last-child td { border-bottom: none; }
.prize-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
</style>

<?php if (isset($db_err)): ?>
<div style="background:#fff2f2;color:#e03e3e;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    数据库连接失败：<?= htmlspecialchars($db_err) ?>
</div>
<?php return; endif; ?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<div class="setting-card" style="margin-bottom:20px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:20px;color:#333;">中奖概率设置</h2>
    <form method="post">
        <div class="setting-row">
            <div class="setting-label">中奖概率</div>
            <div class="setting-input">
                <input type="number" name="prob" value="<?= htmlspecialchars($curProb) ?>"
                       min="0" max="100" step="0.1" required>
            </div>
            <span class="setting-unit">%</span>
        </div>
        <div class="setting-hint">
            设置范围 0 ~ 100，表示用户每次抽奖的中奖概率（百分比）。<br>
            例如设置为 10 表示中奖率 10%，即约每 10 次抽奖会有 1 次中奖（概率事件，非精确均摊）。
        </div>
        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">保存设置</button>
        </div>
    </form>
</div>

<div class="setting-card">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:20px;color:#333;">奖品列表</h2>
    <table class="prize-table">
        <thead>
            <tr>
                <th>奖品</th>
                <th>类型</th>
                <th>说明</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="prize-badge" style="background:#fff0f0;color:#ff6b6b;">8.88元</span></td>
                <td>奖金</td>
                <td>小奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#fff8e1;color:#ff9a00;">18.88元</span></td>
                <td>奖金</td>
                <td>小奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#f0fff0;color:#52c41a;">28.88元</span></td>
                <td>奖金</td>
                <td>小奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#f0f9ff;color:#00a2ff;">38.88元</span></td>
                <td>奖金</td>
                <td>中等奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#f5f0ff;color:#6a00ff;">88.88元</span></td>
                <td>奖金</td>
                <td>中等奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#fff5f8;color:#c9a227;">168.88元</span></td>
                <td>奖金</td>
                <td>大奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#fff0f5;color:#ff4d9d;">888.88元</span></td>
                <td>奖金</td>
                <td>特等奖</td>
            </tr>
            <tr>
                <td><span class="prize-badge" style="background:#f5f5f5;color:#999;">谢谢惠顾</span></td>
                <td>未中奖</td>
                <td>参与奖（谢谢参与）</td>
            </tr>
        </tbody>
    </table>
    <p style="margin-top:16px;font-size:12px;color:#999;">
        奖品为随机发放，以上奖品按中奖概率均等分配（7种奖金各占中奖概率的 1/7）。
    </p>
</div>

</div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end admin-layout -->
</body>
</html>
